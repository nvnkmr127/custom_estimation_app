<?php

namespace App\Core\Events\Estimates;

use App\Core\Events\BaseEvent;

class EstimateApproved extends BaseEvent
{
    public readonly array $snapshot;

    public function __construct(
        public readonly \App\Models\Estimate $estimate,
        public readonly int $approverId,
        public readonly string $approvalType = 'client' // 'client' or 'internal'
    ) {
        $this->snapshot = $estimate->toArray();
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'estimate.approved';
    }

    public function getPayload(): array
    {
        return [
            'estimate_id' => $this->estimate->id,
            'approver_id' => $this->approverId,
            'approval_type' => $this->approvalType,
            'snapshot' => $this->snapshot,
        ];
    }
    public function getEntityType(): string
    {
        return 'estimate';
    }

    public function getEntityId(): int|string|null
    {
        return $this->estimate->id;
    }
}
