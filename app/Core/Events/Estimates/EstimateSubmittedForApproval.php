<?php

namespace App\Core\Events\Estimates;

use App\Core\Events\BaseEvent;
use App\Models\Estimate;

class EstimateSubmittedForApproval extends BaseEvent
{
    private $estimate;

    public function __construct(Estimate $estimate, ?int $userId = null)
    {
        parent::__construct();
        $this->estimate = $estimate;
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
        return [
            'estimate_id' => $this->estimate->id,
            'estimate_number' => $this->estimate->estimate_number,
            'client_name' => $this->estimate->client ? $this->estimate->client->name : 'N/A',
            'grand_total' => $this->estimate->grand_total,
            'currency' => $this->estimate->currency,
            'submitted_by' => $this->getTriggeredBy(),
            'submitted_at' => $this->getOccurredOn()->format('c'),
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
