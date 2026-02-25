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
        $sender = \App\Models\User::find($this->modifierId);
        $client = $this->estimate->client;

        return [
            'estimate_id' => $this->estimate->id,
            'modifier_id' => $this->modifierId,
            'changes' => $this->changes,

            // Modifier/Sender Details
            'sender_id' => $this->modifierId,
            'sender_name' => $sender?->name ?? 'N/A',
            'sender_email' => $sender?->email ?? 'N/A',
            'sender_contact' => $sender?->mobile_number ?? 'N/A',

            // Client Details
            'name' => $client?->name ?? 'N/A',
            'contact_number' => $client?->phone ?? 'N/A',

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
