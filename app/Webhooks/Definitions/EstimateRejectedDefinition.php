<?php

namespace App\Webhooks\Definitions;

use App\Models\Estimate;

class EstimateRejectedDefinition implements WebhookEventDefinitionInterface
{
    use \App\Webhooks\Traits\ShortenUrls;
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

        $expiration = $resource->expiry_date ? $resource->expiry_date->endOfDay() : now()->addDays(30);
        $pdfUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute('portal.download', $expiration, ['estimate' => $resource->id]);

        // Shorten URLs for cleaner webhook payloads
        $expiryDays = $expiration->diffInDays(now());
        $shortPdfUrl = $this->shortenUrl($pdfUrl, $expiryDays);
        $shortEstimateUrl = $this->shortenUrl($resource->public_url, $expiryDays);

        return [
            'id' => $resource->id,
            'reference' => $resource->estimate_number,
            'total' => $resource->grand_total,
            'mobile_number' => $resource->client?->phone,
            'status' => $resource->status,
            'rejected_at' => now()->toIso8601String(),
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
            'total' => 1500.00,
            'mobile_number' => '8688771397',
            'status' => 'rejected',
            'rejected_at' => now()->toIso8601String(),
            'reason' => 'Total exceeds limit for draft status',
            'url' => 'https://example.com/portal/estimates/123?signature=...',
            'pdf' => 'https://example.com/portal/estimates/123/download?signature=...',
            'client' => [
                'name' => 'John Doe',
                'email' => 'wapmedia3@gmail.com',
                'phone' => '8688771397',
            ],
            'creator' => [
                'name' => 'Agent Smith',
                'email' => 'wapmedia3@gmail.com',
                'phone' => '8688771397',
            ],
        ];
    }
}
