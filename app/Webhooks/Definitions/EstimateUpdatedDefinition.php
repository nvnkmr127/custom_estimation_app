<?php

namespace App\Webhooks\Definitions;

use App\Models\Estimate;

class EstimateUpdatedDefinition implements WebhookEventDefinitionInterface
{
    public function name(): string
    {
        return 'estimate.updated';
    }

    public function description(): string
    {
        return 'Triggered when an existing estimate is modified.';
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
            'total' => $resource->total,
            'status' => $resource->status,
            'updated_at' => $resource->updated_at ? $resource->updated_at->toIso8601String() : now()->toIso8601String(),
            'changes' => $resource->getChanges(),
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 123,
            'reference' => 'EST-2024-001',
            'total' => 1750.00,
            'status' => 'sent',
            'updated_at' => now()->toIso8601String(),
            'changes' => [
                'total' => ['old' => 1500.00, 'new' => 1750.00],
                'status' => ['old' => 'draft', 'new' => 'sent'],
            ],
        ];
    }
}
