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
        return [
            'id' => $resource->id,
            'estimate_id' => $resource->estimate_id,
            'chain_id' => $resource->approval_chain_id,
            'status' => $resource->status,
            'created_at' => $resource->created_at->toIso8601String(),
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 1,
            'estimate_id' => 123,
            'chain_id' => 2,
            'status' => 'pending',
            'created_at' => now()->toIso8601String(),
        ];
    }
}
