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
                // Mail, Webhook, Notification listeners are placeholders.
                // We register them here to ensure infrastructure is ready.
                // They will receive all DomainEvents and filter internally or we can split maps later.
            MailListener::class,
            WebhookListener::class,
            NotificationListener::class,
            \App\Listeners\AutomationListener::class,
            \App\Listeners\EstimateIntelligenceListener::class,
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
}
