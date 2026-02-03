<?php

namespace App\Infrastructure\Webhooks\Definitions;

use App\Models\Estimate;

class EstimateSubmittedDefinition implements WebhookEventDefinitionInterface
{
    public function name(): string
    {
        return 'estimate.submitted_for_approval';
    }

    public function description(): string
    {
        return 'Triggered when an estimate is submitted for approval.';
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
            'submitted_at' => now()->toIso8601String(),
            'total' => $resource->total,
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 123,
            'reference' => 'EST-2024-001',
            'submitted_at' => now()->toIso8601String(),
            'total' => 1500.00,
        ];
    }
}
