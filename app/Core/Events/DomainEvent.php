<?php

namespace App\Core\Events;

interface DomainEvent
{
    const PRIORITY_CRITICAL = 'critical';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_LOW = 'low';

    /**
     * Get the unique identifier for the event.
     */
    public function getEventId(): string;

    /**
     * Get the name of the event (e.g., 'order.created').
     */
    public function getEventName(): string;

    /**
     * Get the timestamp when the event occurred.
     */
    public function getOccurredOn(): \DateTimeImmutable;

    /**
     * Get the payload data of the event.
     */
    public function getPayload(): array;

    public function getEntityType(): string;
    public function getEntityId(): int|string|null;
    public function getTriggeredBy(): ?int;
    public function getSource(): string;
    public function getPriority(): string;
}
