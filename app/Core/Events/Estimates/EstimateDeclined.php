<?php

namespace App\Core\Events\Estimates;

use App\Core\Events\BaseEvent;

class EstimateDeclined extends BaseEvent
{
    public function __construct(
        public int $estimateId,
        public ?int $declinerId = null,
        public ?string $reason = null
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'estimate.declined';
    }

    public function getPayload(): array
    {
        return [
            'estimate_id' => $this->estimateId,
            'decliner_id' => $this->declinerId,
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
