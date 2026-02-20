<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the EventDispatcherInterface to the Laravel implementation
        $this->app->bind(
            \App\Core\Events\EventDispatcherInterface::class,
            \App\Infrastructure\Events\LaravelEventDispatcher::class
        );

        // Bind MailGatewayInterface to SmtpMailGateway
        $this->app->bind(
            \App\Services\Mail\Contracts\MailGatewayInterface::class,
            \App\Services\Mail\Gateways\SmtpMailGateway::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            // Register the global event logger
            // Note: LogDomainEvent logic is now handled by LaravelEventDispatcher dispatching LogEvent job directly.

            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                $settings = collect(\App\Models\Setting::getAllCached());

                if ($settings->has('app_name')) {
                    config(['app.name' => $settings['app_name']]);
                }

                if ($settings->has('timezone')) {
                    $timezone = $settings['timezone'];
                    config(['app.timezone' => $timezone]);
                    date_default_timezone_set($timezone);
                }

                view()->share('app_settings', $settings);
                $this->app->instance('app_settings', $settings);
            }


            // Queue Failure Listening
            \Illuminate\Support\Facades\Queue::failing(function (\Illuminate\Queue\Events\JobFailed $event) {
                // Dispatch SystemJobFailed
                try {
                    // Avoid recursion if the failed job IS the LogEvent job
                    if ($event->job->resolveName() === \App\Jobs\LogEvent::class) {
                        return;
                    }

                    app(\App\Core\Events\EventDispatcherInterface::class)->dispatch(
                        new \App\Core\Events\System\SystemJobFailed(
                            $event->job->resolveName(),
                            $event->exception->getMessage(),
                            ['job_id' => $event->job->getJobId()]
                        )
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to dispatch SystemJobFailed event: " . $e->getMessage());
                }
            });

            // View Composer for Layout
            view()->composer('components.app-layout', function ($view) {
                $unreadCount = auth()->check() ? auth()->user()->unreadNotifications->count() : 0;
                // Personalized Pending Approvals Count
                $user = auth()->user();
                $pendingApprovals = 0;

                if ($user) {
                    $pendingApprovals = \App\Models\Estimate::where('approval_status', 'submitted')
                        ->whereHas('approvals', function ($query) use ($user) {
                            $query->where('user_id', $user->id)
                                ->where('status', 'pending');
                        })->count();
                }
                $pendingSuggestions = \App\Models\Product::pending()->count();
                $dlqCount = \App\Models\WebhookDeadLetter::count();

                $view->with(compact('unreadCount', 'pendingApprovals', 'pendingSuggestions', 'dlqCount'));
            });

            // Define the gate for the Log Viewer accessible by admins
            \Illuminate\Support\Facades\Gate::define('viewLogViewer', function ($user) {
                return $user->isAdmin();
            });
        } catch (\Exception $e) {
            //
        }
    }
}
