<?php

namespace App\Webhooks\Definitions;

use App\Models\Client;

class ClientCreatedDefinition implements WebhookEventDefinitionInterface
{
    public function name(): string
    {
        return 'client.created';
    }

    public function description(): string
    {
        return 'Triggered when a new client is added to the system.';
    }

    public function resourceClass(): string
    {
        return Client::class; // Assuming model exists
    }

    public function buildPayload(object $resource): array
    {
        // Assuming Client model structure
        return [
            'id' => $resource->id,
            'name' => $resource->name,
            'email' => $resource->email,
            'company' => $resource->company_name ?? null,
            'created_at' => $resource->created_at->toIso8601String(),
        ];
    }
}
