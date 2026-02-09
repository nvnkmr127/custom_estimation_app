<?php

namespace App\Webhooks\Definitions;

class ApprovalRequestedDefinition implements WebhookEventDefinitionInterface
{
    public function name(): string
    {
        return 'approval.requested';
    }

    public function description(): string
    {
        return 'Triggered when a new approval process is started.';
    }

    public function resourceClass(): string
    {
        return \App\Models\EstimateApproval::class;
    }

    public function buildPayload(object $resource): array
    {
        /** @var \App\Models\EstimateApproval $resource */
        $estimate = $resource->estimate;

        $expiration = $estimate->expiry_date ? $estimate->expiry_date->endOfDay() : now()->addDays(30);
        $pdfUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute('portal.download', $expiration, ['estimate' => $estimate->id]);

        return [
            'id' => $resource->id,
            'status' => $resource->status,
            'total' => $estimate->grand_total,
            'mobile_number' => $estimate->client?->phone,
            'created_at' => $resource->created_at->toIso8601String(),
            'estimate' => [
                'id' => $estimate->id,
                'reference' => $estimate->estimate_number,
                'total' => $estimate->grand_total,
                'url' => $estimate->public_url,
                'pdf' => $pdfUrl,
                'created_by' => $estimate->creator ? [
                    'name' => $estimate->creator->name,
                    'email' => $estimate->creator->email,
                    'phone' => $estimate->creator->mobile_number,
                ] : null,
            ],
            'client' => $estimate->client ? [
                'name' => $estimate->client->name,
                'email' => $estimate->client->email,
                'phone' => $estimate->client->phone,
                'secondary_phone' => $estimate->client->secondary_phone,
            ] : null,
            'approver' => $resource->user ? [
                'name' => $resource->user->name,
                'email' => $resource->user->email,
                'phone' => $resource->user->mobile_number,
            ] : null,
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 1,
            'status' => 'pending',
            'total' => 5000.00,
            'mobile_number' => '123-456-7890',
            'created_at' => now()->toIso8601String(),
            'estimate' => [
                'id' => 123,
                'reference' => 'EST-2024-001',
                'total' => 5000.00,
                'url' => 'https://example.com/portal/estimates/123?signature=...',
                'pdf' => 'https://example.com/portal/estimates/123/download?signature=...',
                'created_by' => [
                    'name' => 'Sales Rep',
                    'email' => 'sales@company.com',
                ],
            ],
            'client' => [
                'name' => 'John Doe',
                'email' => 'client@example.com',
                'phone' => '123-456-7890',
            ],
            'approver' => [
                'name' => 'Manager One',
                'email' => 'manager@company.com',
                'phone' => '555-0199',
            ],
        ];
    }
}
