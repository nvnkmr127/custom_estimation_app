<?php

namespace App\Infrastructure\Webhooks\Definitions;

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

        $expiration = $resource->expiry_date ? $resource->expiry_date->endOfDay() : now()->addDays(30);
        $pdfUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute('portal.download', $expiration, ['estimate' => $resource->id]);

        return [
            'id' => $resource->id,
            'reference' => $resource->reference_number,
            'sent_at' => now()->toIso8601String(),
            'client_email' => $resource->client ? $resource->client->email : null,
            'url' => $resource->public_url,
            'pdf' => $pdfUrl,
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 123,
            'reference' => 'EST-2024-001',
            'sent_at' => now()->toIso8601String(),
            'client_email' => 'client@example.com',
            'url' => 'https://example.com/portal/estimates/123?signature=...',
            'pdf' => 'https://example.com/portal/estimates/123/download?signature=...',
        ];
    }
}
