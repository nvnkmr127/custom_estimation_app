<?php

namespace App\Core\Events\Estimates;

use App\Core\Events\BaseEvent;
use App\Models\Estimate;

class EstimateSubmittedForApproval extends BaseEvent
{
    public readonly array $snapshot;

    public function __construct(
        public readonly Estimate $estimate,
        ?int $userId = null
    ) {
        $this->snapshot = $estimate->toArray();
        parent::__construct();
        if ($userId) {
            $this->triggeredBy = $userId;
        }
    }

    public function getEventName(): string
    {
        return 'estimate.submitted_for_approval';
    }

    public function getPayload(): array
    {
        $sender = $this->estimate->creator;
        $client = $this->estimate->client;

        return [
            'id' => $this->estimate->id,
            'estimate_id' => $this->estimate->id,
            'reference' => $this->estimate->estimate_number,
            'estimate_number' => $this->estimate->estimate_number,
            'submitted_at' => $this->getOccurredOn()->format('c'),
            'total' => $this->estimate->grand_total,
            'currency' => $this->estimate->currency,

            // Sender Details
            'sender_id' => $sender?->id,
            'sender_name' => $sender?->name ?? 'N/A',
            'sender_email' => $sender?->email ?? 'N/A',
            'sender_contact' => $sender?->mobile_number ?? 'N/A',

            // Client Details
            'name' => $client?->name ?? 'N/A',
            'contact_number' => $client?->phone ?? 'N/A',
            'client_name' => $client?->name ?? 'N/A',

            'snapshot' => $this->snapshot,
        ];
    }

    public function getEntityType(): string
    {
        return 'estimate';
    }

    public function getEntityId(): int
    {
        return $this->estimate->id;
    }

    public function getEstimate(): Estimate
    {
        return $this->estimate;
    }
}
