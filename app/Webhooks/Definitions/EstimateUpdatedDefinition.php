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
            'updated_at' => $resource->updated_at->toIso8601String(),
            'changes' => $resource->getChanges(), // Helper to show what changed (optional)
        ];
    }
}
