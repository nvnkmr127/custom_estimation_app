<?php

namespace App\Core\Events\Estimates;

use App\Core\Events\BaseEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EstimateCreated extends BaseEvent
{
    public readonly array $snapshot;

    public function __construct(
        public readonly \App\Models\Estimate $estimate,
        public readonly int $creatorId
    ) {
        $this->snapshot = $estimate->toArray();
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'estimate.created';
    }

    public function getPayload(): array
    {
        $sender = $this->estimate->creator;
        $client = $this->estimate->client;

        return [
            'estimate_id' => $this->estimate->id,
            'creator_id' => $this->creatorId,
            'online_view_url' => $this->estimate->public_url,
            'pdf_download_url' => route('estimates.pdf', $this->estimate->id),

            // Sender Details
            'sender_id' => $sender?->id,
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
