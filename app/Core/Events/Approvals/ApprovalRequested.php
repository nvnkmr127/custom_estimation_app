<?php

namespace App\Core\Events\Approvals;

use App\Core\Events\BaseEvent;

class ApprovalRequested extends BaseEvent
{
    public function __construct(
        public int $estimateId,
        public \App\Models\EstimateApproval $approval,
        public int $approverUserId
    ) {
        parent::__construct();
        $this->priority = self::PRIORITY_CRITICAL;
    }

    public function getEventName(): string
    {
        return 'approval.requested';
    }

    public function getPayload(): array
    {
        return [
            'estimate_id' => $this->estimateId,
            'approval_id' => $this->approval->id,
            'approver_id' => $this->approverUserId,
            'approver_name' => $this->approval->user ? $this->approval->user->name : 'N/A',
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
