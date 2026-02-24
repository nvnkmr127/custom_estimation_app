<?php

namespace App\Infrastructure\Webhooks\Definitions;

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
        return [
            'estimate_id' => $resource->estimate_id,
            'approval_id' => $resource->id,
            'approver_id' => $resource->user_id,
            'approver_name' => $resource->user?->name,
            'comments' => $resource->comments,
            'message' => 'An estimate has been rejected during the approval process.',
            'link' => route('estimates.show', $resource->estimate_id),
        ];
    }

    public function samplePayload(): array
    {
        return [
            'estimate_id' => 123,
            'approval_id' => 45,
            'approver_id' => 5,
            'approver_name' => 'Sarah Manager',
            'comments' => 'Budget exceeds quarterly allocation.',
            'message' => 'An estimate has been rejected during the approval process.',
            'link' => 'https://estimator.onestudio.co.in/estimates/123',
        ];
    }
}
