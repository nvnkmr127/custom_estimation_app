<?php

namespace App\Webhooks\Definitions;

class UserRegisteredDefinition implements WebhookEventDefinitionInterface
{
    public function name(): string
    {
        return 'user.registered';
    }

    public function description(): string
    {
        return 'Triggered when a new user registers.';
    }

    public function resourceClass(): string
    {
        return \App\Models\User::class;
    }

    public function buildPayload(object $resource): array
    {
        /** @var \App\Models\User $resource */
        return [
            'id' => $resource->id,
            'name' => $resource->name,
            'email' => $resource->email,
            'registered_at' => $resource->created_at ? $resource->created_at->toIso8601String() : now()->toIso8601String(),
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 5,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'registered_at' => now()->toIso8601String(),
        ];
    }
}
