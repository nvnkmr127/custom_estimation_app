<?php

namespace App\Core\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

abstract class BaseEvent implements DomainEvent
{
    use Dispatchable, SerializesModels;

    public string $eventId;
    public \DateTimeImmutable $occurredOn;
    public ?int $triggeredBy = null;
    public string $source = 'web';
    public string $priority = 'normal';

    public function __construct()
    {
        $this->eventId = (string) Str::uuid();
        $this->occurredOn = new \DateTimeImmutable();
        $this->triggeredBy = auth()->id();
        $this->source = app()->runningInConsole() ? 'cli' : (request()->is('api/*') ? 'api' : 'web');
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getTriggeredBy(): ?int
    {
        return $this->triggeredBy;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    abstract public function getEventName(): string;
    abstract public function getPayload(): array;
    abstract public function getEntityType(): string;
    abstract public function getEntityId(): int|string|null;
}
