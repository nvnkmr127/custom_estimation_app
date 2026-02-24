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
            'estimate_id' => $resource->estimate_id,
            'approval_id' => $resource->id,
            'approver_id' => $resource->user_id,
            'approver_name' => $resource->user?->name,
            'message' => 'You have a new estimate approval request.',
            'link' => route('approvals.index'),
        ];
    }

    public function samplePayload(): array
    {
        return [
            'estimate_id' => 100,
            'approval_id' => 49,
            'approver_id' => 15,
            'approver_name' => 'Rakesh',
            'message' => 'You have a new estimate approval request.',
            'link' => 'https://estimator.onestudio.co.in/approvals',
        ];
    }
}
