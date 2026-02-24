<?php

namespace App\Infrastructure\Webhooks\Definitions;

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
        return [
            'id' => $resource->id,
            'estimate_id' => $resource->estimate_id,
            'estimate_number' => $resource->estimate->estimate_number,
            'chain_id' => $resource->estimate->approval_chain_id,
            'user' => [
                'id' => $resource->user_id,
                'name' => $resource->user?->name,
                'email' => $resource->user?->email,
                'mobile_number' => $resource->user?->mobile_number,
            ],
            'status' => $resource->status,
            'created_at' => $resource->created_at->toIso8601String(),
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 1,
            'estimate_id' => 123,
            'estimate_number' => 'EST-2024-001',
            'chain_id' => 2,
            'user' => [
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'mobile_number' => '8688771397',
            ],
            'status' => 'pending',
            'created_at' => now()->toIso8601String(),
        ];
    }
}
