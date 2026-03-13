<?php

namespace App\Webhooks\Definitions;

use App\Models\Estimate;

class EstimateDeclinedDefinition implements WebhookEventDefinitionInterface
{
    use \App\Webhooks\Traits\ShortenUrls;
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

        $expiration = $resource->expiry_date ? $resource->expiry_date->endOfDay() : now()->addDays(30);
        $pdfUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute('portal.download', $expiration, ['estimate' => $resource->id]);

        // Shorten URLs for cleaner webhook payloads
        $shortPdfUrl = $this->shortenUrl($pdfUrl, $expiration);
        $shortEstimateUrl = $this->shortenUrl($resource->public_url, $expiration);

        return [
            'id' => $resource->id,
            'reference' => $resource->estimate_number,
            'status' => $resource->status,
            'declined_at' => now()->toIso8601String(), // Ideally fetch from audit log
            'reason' => $resource->client_notes, // Using client_notes as the decline reason
            'url' => $shortEstimateUrl,
            'pdf' => $shortPdfUrl,
            'client' => $resource->client ? [
                'name' => $resource->client->name,
                'email' => $resource->client->email,
                'phone' => $resource->client->phone,
                'secondary_phone' => $resource->client->secondary_phone,
            ] : null,
            'creator' => $resource->creator ? [
                'name' => $resource->creator->name,
                'email' => $resource->creator->email,
                'phone' => $resource->creator->mobile_number,
            ] : null,
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 123,
            'reference' => 'EST-2024-001',
            'status' => 'declined',
            'declined_at' => now()->toIso8601String(),
            'reason' => 'Too expensive compared to competitors.',
            'url' => 'https://example.com/portal/estimates/123?signature=...',
            'pdf' => 'https://example.com/portal/estimates/123/download?signature=...',
            'client' => [
                'name' => 'John Doe',
                'email' => 'wapmedia3@gmail.com',
            ],
            'creator' => [
                'name' => 'Agent Smith',
                'email' => 'wapmedia3@gmail.com',
            ],
        ];
    }
}
