<?php

namespace App\Core\Events\Approvals;

use App\Core\Events\BaseEvent;

class ApprovalRejected extends BaseEvent
{
    public function __construct(
        public int $estimateId,
        public int $approvalId,
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
        return [
            'estimate_id' => $this->estimateId,
            'approval_id' => $this->approvalId,
            'rejector_user_id' => $this->rejectorUserId,
            'reason' => $this->reason,
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
