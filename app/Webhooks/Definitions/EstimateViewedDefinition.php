<?php

namespace App\Webhooks\Definitions;

use App\Models\Estimate;

class EstimateViewedDefinition implements WebhookEventDefinitionInterface
{
    public function name(): string
    {
        return 'estimate.viewed';
    }

    public function description(): string
    {
        return 'Triggered when an estimate is viewed by the client.';
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
            'viewed_at' => now()->toIso8601String(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 123,
            'reference' => 'EST-2024-001',
            'viewed_at' => now()->toIso8601String(),
            'ip_address' => '1.2.3.4',
            'user_agent' => 'Mozilla/5.0...',
        ];
    }
}
