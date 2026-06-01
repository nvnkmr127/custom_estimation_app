<?php

namespace App\Core\Events\Estimates;

use App\Core\Events\BaseEvent;

class EstimateChangesRequested extends BaseEvent
{
    public function __construct(
        public \App\Models\Estimate $estimate,
        public int $requestorId,
        public string $comments
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'estimate.request_changes';
    }

    public function getPayload(): array
    {
        $sender = \App\Models\User::find($this->requestorId);
        $client = $this->estimate->client;

        return [
            'estimate_id' => $this->estimate->id,
            'requestor_id' => $this->requestorId,
            'comments' => $this->comments,
            'estimate_number' => $this->estimate->estimate_number,
            'online_view_url' => $this->estimate->public_url,
            'pdf_download_url' => route('estimates.pdf', $this->estimate->id),

            'sender_id' => $this->requestorId,
            'sender_name' => $sender?->name ?? 'N/A',
            'sender_email' => $sender?->email ?? 'N/A',
            'sender_contact' => $sender?->mobile_number ?? 'N/A',

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
