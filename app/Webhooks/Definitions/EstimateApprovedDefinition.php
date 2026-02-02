<?php

namespace App\Webhooks\Definitions;

use App\Models\Estimate;

class EstimateApprovedDefinition implements WebhookEventDefinitionInterface
{
    public function name(): string
    {
        return 'estimate.approved';
    }

    public function description(): string
    {
        return 'Triggered when an estimate is marked as approved.';
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
            'approved_at' => now()->toIso8601String(), // In real case, fetch from audit log or property
            'url' => route('portal.show', $resource),
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 123,
            'reference' => 'EST-2024-001',
            'status' => 'approved',
            'approved_at' => now()->toIso8601String(),
            'url' => 'https://example.com/portal/estimates/123',
        ];
    }
}
