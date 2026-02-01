<?php

namespace App\Webhooks\Definitions;

use App\Models\Estimate;

class EstimateCreatedDefinition implements WebhookEventDefinitionInterface
{
    public function name(): string
    {
        return 'estimate.created';
    }

    public function description(): string
    {
        return 'Triggered when a new estimate is created.';
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
            'reference' => $resource->reference_number, // Assuming column name
            'client_id' => $resource->client_id,
            'total' => $resource->total,
            'status' => $resource->status,
            'created_at' => $resource->created_at->toIso8601String(),
            'url' => route('portal.show', $resource),
        ];
    }
}
