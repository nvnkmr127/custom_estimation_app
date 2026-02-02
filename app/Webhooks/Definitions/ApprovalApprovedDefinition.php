<?php

namespace App\Webhooks\Definitions;

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
            'approver_id' => $resource->approver_id,
            'status' => 'approved',
            'approved_at' => now()->toIso8601String(),
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 1,
            'estimate_id' => 123,
            'approver_id' => 45,
            'status' => 'approved',
            'approved_at' => now()->toIso8601String(),
        ];
    }
}
