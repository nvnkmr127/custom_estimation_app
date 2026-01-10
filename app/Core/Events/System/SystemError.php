<?php

namespace App\Core\Events\System;

use App\Core\Events\BaseEvent;

class SystemError extends BaseEvent
{
    public function __construct(
        public string $message,
        public array $context = []
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'system.error';
    }

    public function getPayload(): array
    {
        return [
            'message' => $this->message,
            'context' => $this->context,
        ];
    }
    public function getEntityType(): string
    {
        return 'system';
    }

    public function getEntityId(): int|string|null
    {
        return null;
    }
}
