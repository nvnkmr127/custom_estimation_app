<?php

namespace App\Core\Events\Estimates;

use App\Core\Events\BaseEvent;

class EstimateUpdated extends BaseEvent
{
    public readonly array $snapshot;

    public function __construct(
        public readonly \App\Models\Estimate $estimate,
        public readonly int $modifierId,
        public readonly array $changes = []
    ) {
        $this->snapshot = $estimate->toArray();
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'estimate.updated';
    }

    public function getPayload(): array
    {
        return [
            'estimate_id' => $this->estimate->id,
            'modifier_id' => $this->modifierId,
            'changes' => $this->changes,
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
