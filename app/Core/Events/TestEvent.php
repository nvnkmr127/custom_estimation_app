<?php

namespace App\Core\Events;

class TestEvent extends BaseEvent
{
    public function __construct(
        public array $data
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'test.event.dispatched';
    }

    public function getPayload(): array
    {
        return $this->data;
    }

    public function getEntityType(): string
    {
        return 'test_entity'; // dummy value
    }

    public function getEntityId(): int
    {
        return 1; // dummy value
    }
}
