<?php

namespace App\Infrastructure\Webhooks\Definitions;

use App\Models\Estimate;

class EstimateDeclinedDefinition implements WebhookEventDefinitionInterface
{
    public function name(): string
    {
        return 'estimate.declined';
    }

    public function description(): string
    {
        return 'Triggered when a client declines an estimate.';
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
            'declined_at' => now()->toIso8601String(),
            'reason' => 'Too expensive', // Example
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 123,
            'reference' => 'EST-2024-001',
            'declined_at' => now()->toIso8601String(),
            'reason' => 'Project cancelled',
        ];
    }
}
