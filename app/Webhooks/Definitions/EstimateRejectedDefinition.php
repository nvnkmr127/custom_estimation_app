<?php

namespace App\Webhooks\Definitions;

use App\Models\Estimate;

class EstimateRejectedDefinition implements WebhookEventDefinitionInterface
{
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

        return [
            'id' => $resource->id,
            'reference' => $resource->reference_number,
            'status' => $resource->status,
            'rejected_at' => now()->toIso8601String(),
            'url' => $resource->public_url,
            'pdf' => $pdfUrl,
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
            'status' => 'rejected',
            'rejected_at' => now()->toIso8601String(),
            'reason' => 'Total exceeds limit for draft status',
            'url' => 'https://example.com/portal/estimates/123?signature=...',
            'pdf' => 'https://example.com/portal/estimates/123/download?signature=...',
            'client' => [
                'name' => 'John Doe',
                'email' => 'client@example.com',
            ],
            'creator' => [
                'name' => 'Agent Smith',
                'email' => 'agent@company.com',
            ],
        ];
    }
}
