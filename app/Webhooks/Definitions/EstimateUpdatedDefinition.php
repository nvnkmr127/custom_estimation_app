<?php

namespace App\Webhooks\Definitions;

use App\Models\Estimate;

class EstimateUpdatedDefinition implements WebhookEventDefinitionInterface
{
    use \App\Webhooks\Traits\ShortenUrls;
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

        $expiration = $resource->expiry_date ? $resource->expiry_date->endOfDay() : now()->addDays(30);
        $pdfUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute('portal.download', $expiration, ['estimate' => $resource->id]);

        // Shorten URLs for cleaner webhook payloads
        $shortPdfUrl = $this->shortenUrl($pdfUrl, $expiration);
        $shortEstimateUrl = $this->shortenUrl($resource->public_url, $expiration);

        return [
            'id' => $resource->id,
            'reference' => $resource->estimate_number,
            'estimate_number' => $resource->estimate_number,
            'total' => $resource->grand_total,
            'total_amount' => '₹ ' . number_format($resource->grand_total, 2),
            'mobile_number' => $resource->client?->phone,
            'status' => $resource->status,
            'updated_at' => $resource->updated_at ? $resource->updated_at->toIso8601String() : now()->toIso8601String(),
            'url' => $shortEstimateUrl,
            'view_url' => $shortEstimateUrl,
            'pdf' => $shortPdfUrl,
            'changes' => $resource->getChanges(),
            'notifiable_name' => $resource->client?->name ?? 'Valued Client',
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
            'estimate_number' => 'EST-2024-001',
            'total' => 1750.00,
            'total_amount' => '₹ 1,750.00',
            'mobile_number' => '8688771397',
            'status' => 'sent',
            'updated_at' => now()->toIso8601String(),
            'url' => 'https://example.com/portal/estimates/123?signature=...',
            'view_url' => 'https://example.com/portal/estimates/123?signature=...',
            'pdf' => 'https://example.com/portal/estimates/123/download?signature=...',
            'changes' => [
                'total' => ['old' => 1500.00, 'new' => 1750.00],
                'status' => ['old' => 'draft', 'new' => 'sent'],
            ],
            'notifiable_name' => 'John Doe',
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
