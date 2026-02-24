<?php

namespace App\Infrastructure\Webhooks\Definitions;

use App\Models\EstimateApproval;

class ApprovalChangeRequestedDefinition implements WebhookEventDefinitionInterface
{
    public function name(): string
    {
        return 'approval.change_requested';
    }

    public function description(): string
    {
        return 'Triggered when an approver requests changes to an estimate.';
    }

    public function resourceClass(): string
    {
        return EstimateApproval::class;
    }

    public function buildPayload(object $resource): array
    {
        /** @var EstimateApproval $resource */
        return [
            'estimate_id' => $resource->estimate_id,
            'approval_id' => $resource->id,
            'approver_id' => $resource->user_id,
            'approver_name' => $resource->user?->name,
            'comments' => $resource->comments,
            'message' => 'Changes have been requested for this estimate.',
            'link' => route('estimates.show', $resource->estimate_id),
        ];
    }

    public function samplePayload(): array
    {
        return [
            'estimate_id' => 124,
            'approval_id' => 46,
            'approver_id' => 5,
            'approver_name' => 'Sarah Manager',
            'comments' => 'Please adjust the quantity of Item A.',
            'message' => 'Changes have been requested for this estimate.',
            'link' => 'https://estimator.onestudio.co.in/estimates/124',
        ];
    }
}
