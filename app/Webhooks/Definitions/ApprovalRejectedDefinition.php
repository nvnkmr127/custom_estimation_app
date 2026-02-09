<?php

namespace App\Webhooks\Definitions;

use App\Models\EstimateApproval;

class ApprovalRejectedDefinition implements WebhookEventDefinitionInterface
{
    public function name(): string
    {
        return 'approval.rejected';
    }

    public function description(): string
    {
        return 'Triggered when an internal user rejects an estimate during the approval process.';
    }

    public function resourceClass(): string
    {
        return EstimateApproval::class;
    }

    public function buildPayload(object $resource): array
    {
        /** @var EstimateApproval $resource */
        $estimate = $resource->estimate;

        $expiration = $estimate->expiry_date ? $estimate->expiry_date->endOfDay() : now()->addDays(30);
        $pdfUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute('portal.download', $expiration, ['estimate' => $estimate->id]);

        return [
            'id' => $resource->id,
            'status' => 'rejected',
            'comments' => $resource->comments,
            'rejected_at' => $resource->updated_at->toIso8601String(),
            'estimate' => [
                'id' => $estimate->id,
                'reference' => $estimate->estimate_number, // Fix: Use estimate_number as reference
                'total' => $estimate->total,
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
            'id' => 45,
            'status' => 'rejected',
            'comments' => 'Budget exceeds quarterly allocation.',
            'rejected_at' => now()->toIso8601String(),
            'estimate' => [
                'id' => 123,
                'reference' => 'EST-2024-001',
                'total' => 12500.00,
                'url' => 'https://example.com/portal/estimates/123?signature=...',
                'pdf' => 'https://example.com/portal/estimates/123/download?signature=...',
                'created_by' => [
                    'name' => 'John Estimator',
                    'email' => 'john@company.com',
                ],
            ],
            'client' => [
                'name' => 'Big Client Co',
                'email' => 'contact@bigclient.com',
            ],
            'approver' => [
                'name' => 'Sarah Manager',
                'email' => 'sarah@company.com',
            ],
        ];
    }
}
