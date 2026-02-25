<?php

namespace App\Core\Events\Estimates;

use App\Core\Events\BaseEvent;

class EstimateDeclined extends BaseEvent
{
    public function __construct(
        public \App\Models\Estimate $estimate,
        public ?int $declinerId = null,
        public ?string $reason = null
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'estimate.declined';
    }

    public function getPayload(): array
    {
        $sender = $this->estimate->creator;
        $client = $this->estimate->client;

        return [
            'estimate_id' => $this->estimate->id,
            'decliner_id' => $this->declinerId,
            'reason' => $this->reason,
            'estimate_number' => $this->estimate->estimate_number,

            // Sender Details
            'sender_id' => $sender?->id,
            'sender_name' => $sender?->name ?? 'N/A',
            'sender_email' => $sender?->email ?? 'N/A',
            'sender_contact' => $sender?->mobile_number ?? 'N/A',

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
