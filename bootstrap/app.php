<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->prepend(\App\Http\Middleware\ApiSessionAuth::class);

        $middleware->web(replace: [
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class => \App\Http\Middleware\CustomValidateCsrfToken::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'estimate.lock' => \App\Http\Middleware\CheckEstimateLock::class,
        ]);

        $middleware->redirectGuestsTo(
            fn(\Illuminate\Http\Request $request) =>
            config('sso.enabled')
            ? config('sso.auth_core_url') . '/login?redirect=' . urlencode($request->fullUrl())
            : route('login')
        );

        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
            'catch/*',
            'plugins/catch/*',
        ]);
    })
    ->withSchedule(function ($schedule) {
        $schedule->command('estimates:check-expiry')->daily();
        $schedule->command('reminders:send')->everyMinute();
        $schedule->command('notifications:send-digests daily_digest')->daily();
        $schedule->command('notifications:send-digests weekly_digest')->weekly();
        $schedule->command('automation:run-scheduled')->everyMinute();
        $schedule->command('automation:analytics-calculate')->dailyAt('00:05');
        $schedule->command('estimates:nurture')->dailyAt('09:00');
        $schedule->command('approval:check-timeouts')->hourly();

        // Backup Strategy: Checkpoint DB during hours, consolidate at 8 PM
        $schedule->command('backup:checkpoint')
            ->cron('0 10,13,16,19 * * *')
            ->withoutOverlapping();
        $schedule->command('backup:consolidate')
            ->dailyAt('20:00')
            ->withoutOverlapping();

        // Queue Processing
        $schedule->command('queue:work --stop-when-empty --tries=3')
            ->everyMinute()
            ->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Resource not found'], 404);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        });

        $exceptions->report(function (\Throwable $e) {
            try {
                // Check if container has the binding first
                if (app()->bound(\App\Core\Events\EventDispatcherInterface::class)) {
                    app(\App\Core\Events\EventDispatcherInterface::class)->dispatch(
                        new \App\Core\Events\System\SystemError(
                            $e->getMessage(),
                            [
                                'file' => $e->getFile(),
                                'line' => $e->getLine(),
                                'class' => get_class($e),
                            ]
                        )
                    );
                }
            } catch (\Throwable $loggingError) {
                // Fallback logging if event dispatching fails
                // Use stderr to ensure it's visible in tests even if file log fails
                fwrite(STDERR, "CRITICAL FALLBACK: Failed to dispatch SystemError event. Original Error: " . $e->getMessage() . "\n");
                // \Illuminate\Support\Facades\Log::error("Failed to dispatch SystemError event: " . $loggingError->getMessage());
            }

            if ($e instanceof \Illuminate\Database\QueryException) {
                // Security: Filter out sensitive parameters from logs
                $bindings = $e->getBindings();
                $sensitiveKeys = ['password', 'token', 'secret', 'key', 'cvv', 'card'];
                $filteredBindings = array_map(function ($value) use ($sensitiveKeys) {
                    if (is_string($value)) {
                        foreach ($sensitiveKeys as $key) {
                            if (stripos($value, $key) !== false && strlen($value) > 20) return '[REDACTED]';
                        }
                    }
                    return $value;
                }, $bindings);

                \Illuminate\Support\Facades\Log::critical('Database Error: ' . $e->getMessage(), [
                    'sql' => $e->getSql(),
                    'params' => $filteredBindings,
                    'user_id' => auth()->id(),
                ]);
            }
        });
    })->create();
