<?php

namespace App\Infrastructure\Webhooks\Definitions;

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

        // Compute expiration for PDF link (same logic as public_url)
        $expiration = $resource->expiry_date ? $resource->expiry_date->endOfDay() : now()->addDays(30);
        $pdfUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute('portal.download', $expiration, ['estimate' => $resource->id]);

        return [
            'id' => $resource->id,
            'reference' => $resource->reference_number,
            'status' => $resource->status,
            'approved_at' => now()->toIso8601String(), // In real case, fetch from audit log or property
            'url' => $resource->public_url,
            'pdf' => $pdfUrl,
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 123,
            'reference' => 'EST-2024-001',
            'status' => 'approved',
            'approved_at' => now()->toIso8601String(),
            'url' => 'https://example.com/portal/estimates/123?signature=...',
            'pdf' => 'https://example.com/portal/estimates/123/download?signature=...',
        ];
    }
}
