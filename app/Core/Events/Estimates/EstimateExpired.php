<?php

namespace App\Core\Events\Estimates;

use App\Core\Events\BaseEvent;

class EstimateExpired extends BaseEvent
{
    public function __construct(
        public \App\Models\Estimate $estimate
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'estimate.expired';
    }

    public function getPayload(): array
    {
        $client = $this->estimate->client;

        return [
            'estimate_id' => $this->estimate->id,
            'estimate_number' => $this->estimate->estimate_number,

            // Client Details
            'name' => $client?->name ?? 'N/A',
            'contact_number' => $client?->phone ?? 'N/A',
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
