<?php

namespace App\Core\Events\Estimates;

use App\Core\Events\BaseEvent;

class EstimateRejected extends BaseEvent
{
    public function __construct(
        public \App\Models\Estimate $estimate,
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
            'estimate_id' => $this->estimate->id,
            'rejector_id' => $this->rejectorId,
            'reason' => $this->reason,
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
