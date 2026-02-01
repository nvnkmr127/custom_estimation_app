<?php

namespace App\Providers;

use App\Webhooks\WebhookEventRegistry;
use App\Webhooks\Definitions\EstimateCreatedDefinition;
use App\Webhooks\Definitions\EstimateUpdatedDefinition;
use App\Webhooks\Definitions\ClientCreatedDefinition;
use Illuminate\Support\ServiceProvider;

class WebhookServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WebhookEventRegistry::class, function ($app) {
            $registry = new WebhookEventRegistry();

            // Register Events
            $registry->register(new EstimateCreatedDefinition());
            $registry->register(new EstimateUpdatedDefinition());
            $registry->register(new ClientCreatedDefinition());
            // Add others (sent, accepted) similarly...

            return $registry;
        });
    }

    public function boot(): void
    {
        //
    }
}
