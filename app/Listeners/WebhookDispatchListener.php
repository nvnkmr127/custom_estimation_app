<?php

namespace App\Listeners;

use App\Core\Events\DomainEvent;
use App\Models\WebhookSubscription;
use App\Jobs\SendOutboundWebhook;
use Illuminate\Support\Facades\Log;

class WebhookDispatchListener
{
    public function __construct(
        protected \App\Webhooks\WebhookEventDispatcher $dispatcher
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        $this->dispatcher->dispatch($event);
    }
}
