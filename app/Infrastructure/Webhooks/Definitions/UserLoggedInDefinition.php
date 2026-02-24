<?php

namespace App\Infrastructure\Webhooks\Definitions;

use App\Models\User;

class UserLoggedInDefinition implements WebhookEventDefinitionInterface
{
    public function name(): string
    {
        return 'user.logged_in';
    }

    public function description(): string
    {
        return 'Triggered when a user successfully logs into the application.';
    }

    public function resourceClass(): string
    {
        return User::class;
    }

    public function buildPayload(object $resource): array
    {
        /** @var User $resource */
        return [
            'id' => $resource->id,
            'name' => $resource->name,
            'email' => $resource->email,
            'mobile_number' => $resource->mobile_number,
            'role' => $resource->role,
            'logged_in_at' => now()->toIso8601String(),
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'mobile_number' => '8688771397',
            'role' => 'estimator',
            'logged_in_at' => now()->toIso8601String(),
        ];
    }
}
