<?php

namespace App\Infrastructure\Webhooks\Definitions;

use App\Models\Estimate;

class EstimateExpiredDefinition implements WebhookEventDefinitionInterface
{
    public function name(): string
    {
        return 'estimate.expired';
    }

    public function description(): string
    {
        return 'Triggered when an estimate reaches its expiration date.';
    }

    public function resourceClass(): string
    {
        return Estimate::class;
    }

    public function buildPayload(object $resource): array
    {
        /** @var Estimate $resource */
        return [
            'id' => $resource->id,
            'estimate_number' => $resource->estimate_number,
            'user' => [
                'id' => $resource->created_by,
                'name' => $resource->creator?->name,
                'email' => $resource->creator?->email,
                'mobile_number' => $resource->creator?->mobile_number,
            ],
            'expired_at' => $resource->expires_at ? $resource->expires_at->toIso8601String() : now()->toIso8601String(),
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 123,
            'estimate_number' => 'EST-2024-001',
            'user' => [
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'mobile_number' => '8688771397',
            ],
            'expired_at' => now()->toIso8601String(),
        ];
    }
}
