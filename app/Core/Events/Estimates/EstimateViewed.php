<?php

namespace App\Core\Events\Estimates;

use App\Core\Events\BaseEvent;

class EstimateViewed extends BaseEvent
{
    public function __construct(
        public \App\Models\Estimate $estimate,
        public ?int $viewerId, // null if anonymous/public link
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
            'estimate_id' => $this->estimate->id,
            'viewer_id' => $this->viewerId,
            'ip_address' => $this->ipAddress,
            'estimate_number' => $this->estimate->estimate_number,
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
