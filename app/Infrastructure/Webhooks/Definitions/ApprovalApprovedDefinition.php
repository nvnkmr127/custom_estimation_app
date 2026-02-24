<?php

namespace App\Infrastructure\Webhooks\Definitions;

class ApprovalApprovedDefinition implements WebhookEventDefinitionInterface
{
    public function name(): string
    {
        return 'approval.approved';
    }

    public function description(): string
    {
        return 'Triggered when an approval step is successfully approved.';
    }

    public function resourceClass(): string
    {
        return \App\Models\EstimateApproval::class;
    }

    public function buildPayload(object $resource): array
    {
        /** @var \App\Models\EstimateApproval $resource */
        return [
            'id' => $resource->id,
            'estimate_id' => $resource->estimate_id,
            'estimate_number' => $resource->estimate->estimate_number,
            'user' => [
                'id' => $resource->user_id,
                'name' => $resource->user?->name,
                'email' => $resource->user?->email,
                'mobile_number' => $resource->user?->mobile_number,
            ],
            'status' => 'approved',
            'approved_at' => now()->toIso8601String(),
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 1,
            'estimate_id' => 123,
            'estimate_number' => 'EST-2024-001',
            'user' => [
                'id' => 45,
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'mobile_number' => '8688771397',
            ],
            'status' => 'approved',
            'approved_at' => now()->toIso8601String(),
        ];
    }
}
