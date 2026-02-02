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
        return [
            'id' => $resource->id,
            'estimate_id' => $resource->estimate_id,
            'estimate_number' => $resource->estimate->estimate_number,
            'user' => [
                'id' => $resource->user_id,
                'name' => $resource->user->name,
            ],
            'comments' => $resource->comments,
            'rejected_at' => $resource->updated_at->toIso8601String(),
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 45,
            'estimate_id' => 123,
            'estimate_number' => 'EST-2024-001',
            'user' => [
                'id' => 5,
                'name' => 'Sarah Manager',
            ],
            'comments' => 'Budget exceeds quarterly allocation.',
            'rejected_at' => now()->toIso8601String(),
        ];
    }
}
