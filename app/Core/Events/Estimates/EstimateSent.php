<?php

namespace App\Core\Events\Estimates;

use App\Core\Events\BaseEvent;

class EstimateSent extends BaseEvent
{
    public function __construct(
        public int $estimateId,
        public int $senderId,
        public string $method // e.g., 'email', 'bulk'
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'estimate.sent';
    }

    public function getPayload(): array
    {
        return [
            'estimate_id' => $this->estimateId,
            'sender_id' => $this->senderId,
            'method' => $this->method,
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
