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
            'estimate_id' => $resource->estimate_id,
            'approval_id' => $resource->id,
            'approver_id' => $resource->user_id,
            'approver_name' => $resource->user?->name,
            'message' => 'An estimate approval step has been approved.',
            'link' => route('estimates.show', $resource->estimate_id),
        ];
    }

    public function samplePayload(): array
    {
        return [
            'estimate_id' => 123,
            'approval_id' => 1,
            'approver_id' => 45,
            'approver_name' => 'Jane Smith',
            'message' => 'An estimate approval step has been approved.',
            'link' => 'https://estimator.onestudio.co.in/estimates/123',
        ];
    }
}
