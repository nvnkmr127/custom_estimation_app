<?php

namespace App\Core\Events;

class ScheduledEvent extends BaseEvent
{
    public function __construct(
        private string $eventName,
        private array $payload,
        private string $entityType = 'system',
        private string|int|null $entityId = null
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getEntityId(): int|string|null
    {
        return $this->entityId;
    }
}
