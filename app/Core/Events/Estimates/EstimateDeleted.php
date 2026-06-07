<?php

namespace App\Core\Events\Estimates;

use App\Core\Events\BaseEvent;

class EstimateDeleted extends BaseEvent
{
    public readonly array $snapshot;

    public function __construct(
        public readonly \App\Models\Estimate $estimate,
        public readonly int $deletedById
    ) {
        $this->snapshot = $estimate->toArray();
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'estimate.deleted';
    }

    public function getPayload(): array
    {
        $sender = $this->estimate->creator;
        $client = $this->estimate->client;

        return [
            'estimate_id' => $this->estimate->id,
            'deleted_by_id' => $this->deletedById,
            'sender_id' => $sender?->id,
            'sender_name' => $sender?->name ?? 'N/A',
            'sender_email' => $sender?->email ?? 'N/A',
            'sender_contact' => $sender?->mobile_number ?? 'N/A',
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
