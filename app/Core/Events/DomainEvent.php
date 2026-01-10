<?php

namespace App\Core\Events;

interface DomainEvent
{
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
}
