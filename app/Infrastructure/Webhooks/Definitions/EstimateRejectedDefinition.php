<?php

namespace App\Infrastructure\Webhooks\Definitions;

use App\Models\Estimate;

class EstimateRejectedDefinition implements WebhookEventDefinitionInterface
{
    public function name(): string
    {
        return 'estimate.rejected';
    }

    public function description(): string
    {
        return 'Triggered when an estimate is rejected by an internal approver.';
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
            'status' => $resource->status,
            'rejected_at' => now()->toIso8601String(),
            'reason' => 'Internal policy check failed', // Example
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 123,
            'reference' => 'EST-2024-001',
            'status' => 'rejected',
            'rejected_at' => now()->toIso8601String(),
            'reason' => 'Total exceeds limit for draft status',
        ];
    }
}
