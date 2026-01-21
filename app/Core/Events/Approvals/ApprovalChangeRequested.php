<?php

namespace App\Core\Events\Approvals;

use App\Core\Events\BaseEvent;

class ApprovalChangeRequested extends BaseEvent
{
    public function __construct(
        public int $estimateId,
        public int $approvalId,
        public int $approverUserId,
        public string $comments
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'approval.change_requested';
    }

    public function getPayload(): array
    {
        return [
            'estimate_id' => $this->estimateId,
            'approval_id' => $this->approvalId,
            'approver_user_id' => $this->approverUserId,
            'comments' => $this->comments,
        ];
    }
    public function getEntityType(): string
    {
        return 'approval';
    }

    public function getEntityId(): int|string|null
    {
        return $this->approvalId;
    }
}
