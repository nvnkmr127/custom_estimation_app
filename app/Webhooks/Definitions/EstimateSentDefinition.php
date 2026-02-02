<?php

namespace App\Webhooks\Definitions;

use App\Models\Estimate;

class EstimateSentDefinition implements WebhookEventDefinitionInterface
{
    public function name(): string
    {
        return 'estimate.sent';
    }

    public function description(): string
    {
        return 'Triggered when an estimate is sent to a client.';
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
            'sent_at' => now()->toIso8601String(),
            'client_email' => $resource->client ? $resource->client->email : null,
            'url' => route('portal.show', $resource),
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 123,
            'reference' => 'EST-2024-001',
            'sent_at' => now()->toIso8601String(),
            'client_email' => 'client@example.com',
            'url' => 'https://example.com/portal/estimates/123',
        ];
    }
}
