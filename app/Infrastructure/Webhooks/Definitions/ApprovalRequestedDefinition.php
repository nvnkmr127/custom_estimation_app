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

            // Client Details (for context)
            'name' => $client?->name ?? 'N/A',
            'contact_number' => $client?->phone ?? 'N/A',

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
            'approver_email' => 'rakesh@example.com',
            'approver_contact' => '+91 90000 11111',
            'sender_id' => 1,
            'sender_name' => 'Master Estimator',
            'sender_email' => 'estimator@example.com',
            'sender_contact' => '+91 80000 22222',
            'name' => 'John Doe',
            'contact_number' => '+91 98765 43210',
            'message' => 'You have a new estimate approval request.',
            'link' => 'https://estimator.onestudio.co.in/approvals',
        ];
    }
}
