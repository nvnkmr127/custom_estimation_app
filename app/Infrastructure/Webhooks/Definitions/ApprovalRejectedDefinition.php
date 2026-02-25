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
        $estimate = $resource->estimate;
        $client = $estimate?->client;
        $sender = $estimate?->creator;
        $approver = $resource->user;

        return [
            'estimate_id' => $resource->estimate_id,
            'approval_id' => $resource->id,

            // Approver Details
            'approver_id' => $resource->user_id,
            'approver_name' => $approver?->name ?? 'N/A',
            'approver_email' => $approver?->email ?? 'N/A',
            'approver_contact' => $approver?->mobile_number ?? 'N/A',

            // Sender Details (Who created/sent for approval)
            'sender_id' => $sender?->id,
            'sender_name' => $sender?->name ?? 'N/A',
            'sender_email' => $sender?->email ?? 'N/A',
            'sender_contact' => $sender?->mobile_number ?? 'N/A',

            // Client Details
            'name' => $client?->name ?? 'N/A',
            'contact_number' => $client?->phone ?? 'N/A',

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
            'approver_email' => 'sarah@example.com',
            'approver_contact' => '+91 90000 33333',
            'sender_id' => 1,
            'sender_name' => 'Master Estimator',
            'sender_email' => 'estimator@example.com',
            'sender_contact' => '+91 80000 22222',
            'name' => 'John Doe',
            'contact_number' => '+91 98765 43210',
            'comments' => 'Budget exceeds quarterly allocation.',
            'message' => 'An estimate has been rejected during the approval process.',
            'link' => 'https://estimator.onestudio.co.in/estimates/123',
        ];
    }
}
