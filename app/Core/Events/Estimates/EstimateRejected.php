<?php

namespace App\Core\Events\Estimates;

use App\Core\Events\BaseEvent;

class EstimateRejected extends BaseEvent
{
    public function __construct(
        public int $estimateId,
        public int $rejectorId,
        public ?string $reason = null
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'estimate.rejected';
    }

    public function getPayload(): array
    {
        return [
            'estimate_id' => $this->estimateId,
            'rejector_id' => $this->rejectorId,
            'reason' => $this->reason,
        ];
    }
    public function getEntityType(): string
    {
        return 'estimate';
    }

    public function getEntityId(): int|string|null
    {
        return $this->estimateId;
    }
}
