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
        /** @var Client $resource */
        return [
            'id' => $resource->id,
            'name' => $resource->name,
            'email' => $resource->email,
            'phone' => $resource->phone,
            'secondary_phone' => $resource->secondary_phone,
            'company' => $resource->company_name ?? null,
            'address' => $resource->address,
            'city' => $resource->city,
            'state' => $resource->state,
            'zip' => $resource->zip,
            'created_at' => $resource->created_at ? $resource->created_at->toIso8601String() : now()->toIso8601String(),
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 789,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '123-456-7890',
            'secondary_phone' => '098-765-4321',
            'company' => 'Acme Corp',
            'address' => '123 Main St',
            'city' => 'Metropolis',
            'state' => 'NY',
            'zip' => '10001',
            'created_at' => now()->toIso8601String(),
        ];
    }
}
