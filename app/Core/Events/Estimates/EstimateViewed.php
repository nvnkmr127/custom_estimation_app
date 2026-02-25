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
        $client = $this->estimate->client;
        $viewer = $this->viewerId ? \App\Models\User::find($this->viewerId) : null;

        return [
            'estimate_id' => $this->estimate->id,
            'viewer_id' => $this->viewerId,
            'ip_address' => $this->ipAddress,
            'estimate_number' => $this->estimate->estimate_number,

            // Viewer Details (if internal)
            'viewer_name' => $viewer?->name ?? ($this->viewerId ? 'N/A' : 'Client/Guest'),
            'viewer_email' => $viewer?->email ?? 'N/A',

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
