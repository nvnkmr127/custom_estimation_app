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
        $estimate = $resource->estimate;
        $client = $estimate?->client;

        return [
            'estimate_id' => $resource->estimate_id,
            'approval_id' => $resource->id,

            // Approver Details
            'approver_id' => $resource->user_id,
            'approver_name' => $resource->user?->name ?? 'N/A',
            'approver_email' => $resource->user?->email ?? 'N/A',
            'approver_contact' => $resource->user?->mobile_number ?? 'N/A',

            // Sender Details (Who created/sent for approval)
            'sender_id' => $estimate?->creator?->id,
            'sender_name' => $estimate?->creator?->name ?? 'N/A',
            'sender_email' => $estimate?->creator?->email ?? 'N/A',
            'sender_contact' => $estimate?->creator?->mobile_number ?? 'N/A',

            // Client Details
            'name' => $client?->name ?? 'N/A',
            'contact_number' => $client?->phone ?? 'N/A',

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
            'approver_email' => 'jane@example.com',
            'approver_contact' => '+91 90000 11111',
            'sender_id' => 1,
            'sender_name' => 'Master Estimator',
            'sender_email' => 'estimator@example.com',
            'sender_contact' => '+91 80000 22222',
            'name' => 'John Doe',
            'contact_number' => '+91 98765 43210',
            'message' => 'An estimate approval step has been approved.',
            'link' => 'https://estimator.onestudio.co.in/estimates/123',
        ];
    }
}
