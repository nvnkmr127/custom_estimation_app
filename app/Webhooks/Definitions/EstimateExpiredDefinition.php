<?php

namespace App\Webhooks\Definitions;

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
            'reference' => $resource->reference_number,
            'expired_at' => $resource->expires_at ? $resource->expires_at->toIso8601String() : now()->toIso8601String(),
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 123,
            'reference' => 'EST-2024-001',
            'expired_at' => now()->toIso8601String(),
        ];
    }
}
