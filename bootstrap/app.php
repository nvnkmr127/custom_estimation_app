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
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
    })
    ->withSchedule(function ($schedule) {
        $schedule->command('estimates:check-expiry')->daily();
        $schedule->command('reminders:send')->everyMinute();
        $schedule->command('notifications:send-digests daily_digest')->daily();
        $schedule->command('notifications:send-digests weekly_digest')->weekly();
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
                \Illuminate\Support\Facades\Log::critical('Database Error: ' . $e->getMessage(), [
                    'sql' => $e->getSql(),
                    'params' => $e->getBindings(),
                    'user_id' => auth()->id(),
                ]);
            }
        });
    })->create();
