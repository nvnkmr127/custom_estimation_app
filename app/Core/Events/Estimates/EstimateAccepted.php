<?php

namespace App\Core\Events\Estimates;

use App\Core\Events\BaseEvent;

class EstimateAccepted extends BaseEvent
{
    public readonly array $snapshot;

    public function __construct(
        public readonly \App\Models\Estimate $estimate,
        public readonly int $accepterId,
        public readonly string $source = 'client' // 'client' or 'manual'
    ) {
        $this->snapshot = $estimate->toArray();
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'estimate.accepted';
    }

    public function getPayload(): array
    {
        return [
            'estimate_id' => $this->estimate->id,
            'accepter_id' => $this->accepterId,
            'source' => $this->source,
            'online_view_url' => $this->estimate->public_url,
            'pdf_download_url' => route('estimates.pdf', $this->estimate->id),
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
