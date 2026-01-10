<?php

namespace App\Core\Events\Estimates;

use App\Core\Events\BaseEvent;

class EstimateExpired extends BaseEvent
{
    public function __construct(
        public int $estimateId
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'estimate.expired';
    }

    public function getPayload(): array
    {
        return [
            'estimate_id' => $this->estimateId,
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
