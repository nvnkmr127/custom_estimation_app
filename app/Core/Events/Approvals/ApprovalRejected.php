<?php

namespace App\Core\Events\Approvals;

use App\Core\Events\BaseEvent;

class ApprovalRejected extends BaseEvent
{
    public function __construct(
        public int $estimateId,
        public \App\Models\EstimateApproval $approval,
        public int $rejectorUserId,
        public ?string $reason = null
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'approval.rejected';
    }

    public function getPayload(): array
    {
        $estimate = $this->approval->estimate;
        $client = $estimate?->client;
        $sender = $estimate?->creator;
        $approver = $this->approval->user;

        return [
            'estimate_id' => $this->estimateId,
            'approval_id' => $this->approval->id,

            // Approver/Rejector Details
            'approver_id' => $this->rejectorUserId,
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

            'reason' => $this->reason,
            'message' => 'An estimate has been rejected during the approval process.',
            'link' => route('estimates.show', $this->estimateId),
        ];
    }
    public function getEntityType(): string
    {
        return 'approval';
    }

    public function getEntityId(): int|string|null
    {
        return $this->approval->id;
    }
}
