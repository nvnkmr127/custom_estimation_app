<?php

namespace App\Webhooks\Definitions;

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
            'id' => $resource->id,
            'estimate_id' => $resource->estimate_id,
            'estimate_number' => $resource->estimate->estimate_number,
            'user' => [
                'id' => $resource->user_id,
                'name' => $resource->user->name,
            ],
            'comments' => $resource->comments,
            'requested_at' => $resource->updated_at->toIso8601String(),
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 46,
            'estimate_id' => 124,
            'estimate_number' => 'EST-2024-002',
            'user' => [
                'id' => 5,
                'name' => 'Sarah Manager',
            ],
            'comments' => 'Please adjust the quantity of Item A.',
            'requested_at' => now()->toIso8601String(),
        ];
    }
}
