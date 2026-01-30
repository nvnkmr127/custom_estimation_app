<?php

namespace App\Core\Events\Estimates;

use App\Core\Events\BaseEvent;
use App\Models\Estimate;

class EstimateSent extends BaseEvent
{
    public readonly array $snapshot;

    public function __construct(
        public readonly Estimate $estimate,
        public readonly int $senderId,
        public readonly string $method // e.g., 'email', 'bulk'
    ) {
        $this->snapshot = $estimate->toArray();
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'estimate.sent';
    }

    public function getPayload(): array
    {
        return [
            'estimate_id' => $this->estimate->id,
            'estimate_number' => $this->estimate->estimate_number,
            'client_name' => $this->estimate->client ? $this->estimate->client->name : 'N/A',
            'grand_total' => $this->estimate->grand_total,
            'currency' => $this->estimate->currency,
            'sender_id' => $this->senderId,
            'method' => $this->method,
            'view_link' => $this->estimate->public_url,
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
