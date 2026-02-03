<?php

namespace App\Infrastructure\Webhooks\Definitions;

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
        /** @var Client $resource */
        return [
            'id' => $resource->id,
            'name' => $resource->name,
            'email' => $resource->email,
            'company' => $resource->company_name ?? null,
            'created_at' => $resource->created_at ? $resource->created_at->toIso8601String() : now()->toIso8601String(),
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 789,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'company' => 'Acme Corp',
            'created_at' => now()->toIso8601String(),
        ];
    }
}
