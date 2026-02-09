<?php

namespace App\Webhooks\Definitions;

use App\Models\Estimate;

class EstimateCreatedDefinition implements WebhookEventDefinitionInterface
{
    public function name(): string
    {
        return 'estimate.created';
    }

    public function description(): string
    {
        return 'Triggered when a new estimate is created.';
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
            'client_id' => $resource->client_id,
            'total' => $resource->grand_total,
            'mobile_number' => $resource->client?->phone,
            'status' => $resource->status,
            'created_at' => $resource->created_at ? $resource->created_at->toIso8601String() : now()->toIso8601String(),
            'url' => $resource->public_url,
            'pdf' => $pdfUrl,
            'client' => $resource->client ? [
                'name' => $resource->client->name,
                'email' => $resource->client->email,
                'phone' => $resource->client->phone,
                'secondary_phone' => $resource->client->secondary_phone,
                'address' => $resource->client->address,
                'city' => $resource->client->city,
                'state' => $resource->client->state,
                'zip' => $resource->client->zip,
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
            'client_id' => 456,
            'total' => 1500.00,
            'mobile_number' => '123-456-7890',
            'status' => 'draft',
            'created_at' => now()->toIso8601String(),
            'url' => 'https://example.com/portal/estimates/123?signature=...',
            'pdf' => 'https://example.com/portal/estimates/123/download?signature=...',
            'client' => [
                'name' => 'John Doe',
                'email' => 'client@example.com',
                'phone' => '123-456-7890',
                'secondary_phone' => '098-765-4321',
                'address' => '123 Main St',
                'city' => 'Anytown',
                'state' => 'State',
                'zip' => '12345',
            ],
            'creator' => [
                'name' => 'Agent Smith',
                'email' => 'agent@company.com',
                'phone' => '555-0199',
            ],
        ];
    }
}
