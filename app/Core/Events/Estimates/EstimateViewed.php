<?php

namespace App\Core\Events\Estimates;

use App\Core\Events\BaseEvent;

class EstimateViewed extends BaseEvent
{
    public function __construct(
        public int $estimateId,
        public ?int $viewerId, // null if anonymous/public lin
        public ?string $ipAddress = null
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'estimate.viewed';
    }

    public function getPayload(): array
    {
        return [
            'estimate_id' => $this->estimateId,
            'viewer_id' => $this->viewerId,
            'ip_address' => $this->ipAddress,
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
