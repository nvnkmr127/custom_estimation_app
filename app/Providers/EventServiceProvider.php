<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Core\Events\DomainEvent;
use App\Listeners\AuditListener;
use App\Listeners\MailListener;
use App\Listeners\WebhookListener;
use App\Listeners\NotificationListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        DomainEvent::class => [
            AuditListener::class,
            MailListener::class,
            NotificationListener::class,
            \App\Listeners\AutomationListener::class,
            \App\Listeners\EstimateIntelligenceListener::class,
            \App\Listeners\ConversionTrackingListener::class,
            \App\Listeners\PerfexSyncListener::class,
        ],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [
        \App\Listeners\QueueLifecycleSubscriber::class,
    ];

    /**
     * Register any events for your application.
     */
    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }

    public function boot(): void
    {
        // Explicitly bind the interface to the listener to ensure polymorphic dispatch
        \Illuminate\Support\Facades\Event::listen(
            DomainEvent::class,
            [\App\Listeners\WebhookDispatchListener::class, 'handle']
        );
    }
}
