<?php

namespace App\Webhooks\Definitions;

use App\Models\Estimate;

class EstimateUpdatedDefinition implements WebhookEventDefinitionInterface
{
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

        return [
            'id' => $resource->id,
            'reference' => $resource->reference_number,
            'total' => $resource->total,
            'mobile_number' => $resource->client?->phone,
            'status' => $resource->status,
            'updated_at' => $resource->updated_at ? $resource->updated_at->toIso8601String() : now()->toIso8601String(),
            'url' => $resource->public_url,
            'pdf' => $pdfUrl,
            'changes' => $resource->getChanges(),
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
            'total' => 1750.00,
            'mobile_number' => '123-456-7890',
            'status' => 'sent',
            'updated_at' => now()->toIso8601String(),
            'url' => 'https://example.com/portal/estimates/123?signature=...',
            'pdf' => 'https://example.com/portal/estimates/123/download?signature=...',
            'changes' => [
                'total' => ['old' => 1500.00, 'new' => 1750.00],
                'status' => ['old' => 'draft', 'new' => 'sent'],
            ],
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
