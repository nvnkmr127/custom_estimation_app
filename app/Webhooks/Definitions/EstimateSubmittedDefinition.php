<?php

namespace App\Webhooks\Definitions;

use App\Models\Estimate;

class EstimateSubmittedDefinition implements WebhookEventDefinitionInterface
{
    public function name(): string
    {
        return 'estimate.submitted_for_approval';
    }

    public function description(): string
    {
        return 'Triggered when an estimate is submitted for approval.';
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
            'reference' => $resource->estimate_number,
            'status' => $resource->status,
            'submitted_at' => now()->toIso8601String(),
            'total' => $resource->grand_total,
            'mobile_number' => $resource->client?->phone,
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
            'status' => 'submitted',
            'submitted_at' => now()->toIso8601String(),
            'total' => 1500.00,
            'mobile_number' => '123-456-7890',
            'url' => 'https://example.com/portal/estimates/123?signature=...',
            'pdf' => 'https://example.com/portal/estimates/123/download?signature=...',
            'client' => [
                'name' => 'John Doe',
                'email' => 'client@example.com',
                'phone' => '123-456-7890',
            ],
            'creator' => [
                'name' => 'Agent Smith',
                'email' => 'agent@company.com',
                'phone' => '555-0199',
            ],
        ];
    }
}
