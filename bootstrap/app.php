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

        $middleware->validateCsrfTokens(except: [
            '/webhooks/*',
            '/catch/*',
        ]);
    })
    ->withSchedule(function ($schedule) {
        $schedule->command('estimates:check-expiry')->daily();
        $schedule->command('reminders:send')->everyMinute();
        $schedule->command('notifications:send-digests daily_digest')->daily();
        $schedule->command('notifications:send-digests weekly_digest')->weekly();

        // Automation Engine: Run every minute to check for due cron-based/scheduled automations
        $schedule->command('automation:run-scheduled')->everyMinute();

        // Automation Engine: Daily analytics aggregation (calculates stats for previous day)
        $schedule->command('automation:analytics-calculate')->dailyAt('00:05');

        // Estimate Nurturing: Process intelligent follow-ups daily
        $schedule->command('estimates:nurture')->dailyAt('09:00');

        // Approval Management: Check for expiring/timeout approvals hourly
        $schedule->command('approval:check-timeouts')->hourly();

        // CRM Sync: Ping the external Perfex CRM cron every 5 minutes
        $schedule->command('perfex:cron-ping')->everyFiveMinutes();

        // Queue Processing: Process pending jobs every minute (for environments without a dedicated worker)
        // We include 'default' and 'webhooks' queues.
        $schedule->command('queue:work --queue=default,webhooks --stop-when-empty --tries=3')
            ->everyMinute()
            ->withoutOverlapping();

        // Queue Maintenance: Cleanup old failed jobs and batches
        $schedule->command('queue:prune-failed --hours=24')->daily();
        $schedule->command('queue:prune-batches --hours=24')->daily();
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
