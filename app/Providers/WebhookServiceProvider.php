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
            $registry->register(new \App\Webhooks\Definitions\EstimateCreatedDefinition());
            $registry->register(new \App\Webhooks\Definitions\EstimateUpdatedDefinition());
            $registry->register(new \App\Webhooks\Definitions\EstimateDeletedDefinition());
            $registry->register(new \App\Webhooks\Definitions\EstimateSentDefinition());
            $registry->register(new \App\Webhooks\Definitions\EstimateApprovedDefinition());
            $registry->register(new \App\Webhooks\Definitions\EstimateAcceptedDefinition());
            $registry->register(new \App\Webhooks\Definitions\EstimateRejectedDefinition());
            $registry->register(new \App\Webhooks\Definitions\EstimateDeclinedDefinition());
            $registry->register(new \App\Webhooks\Definitions\EstimateViewedDefinition());
            $registry->register(new \App\Webhooks\Definitions\EstimateSubmittedDefinition());
            $registry->register(new \App\Webhooks\Definitions\EstimateExpiredDefinition());

            $registry->register(new \App\Webhooks\Definitions\ClientCreatedDefinition());
            $registry->register(new \App\Webhooks\Definitions\CommentAddedDefinition());
            $registry->register(new \App\Webhooks\Definitions\UserRegisteredDefinition());

            $registry->register(new \App\Webhooks\Definitions\ApprovalRequestedDefinition());
            $registry->register(new \App\Webhooks\Definitions\ApprovalApprovedDefinition());
            $registry->register(new \App\Webhooks\Definitions\ApprovalRejectedDefinition());
            $registry->register(new \App\Webhooks\Definitions\ApprovalChangeRequestedDefinition());

            $registry->register(new \App\Webhooks\Definitions\UserLoggedInDefinition());

            return $registry;
        });
    }

    public function boot(): void
    {
        //
    }
}
