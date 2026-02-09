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

        $expiration = $resource->expiry_date ? $resource->expiry_date->endOfDay() : now()->addDays(30);
        $pdfUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute('portal.download', $expiration, ['estimate' => $resource->id]);

        return [
            'id' => $resource->id,
            'reference' => $resource->estimate_number,
            'total' => $resource->grand_total,
            'mobile_number' => $resource->client?->phone,
            'status' => $resource->status,
            'approved_at' => now()->toIso8601String(), // In real case, fetch from audit log or property
            'signed_at' => $resource->signed_at ? $resource->signed_at->toIso8601String() : null,
            'signer_ip' => $resource->signer_ip,
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
            'total' => 1500.00,
            'mobile_number' => '123-456-7890',
            'status' => 'approved',
            'approved_at' => now()->toIso8601String(),
            'signed_at' => now()->toIso8601String(),
            'signer_ip' => '192.168.1.1',
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
