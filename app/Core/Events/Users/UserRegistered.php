<?php

namespace App\Core\Events\Users;

use App\Core\Events\BaseEvent;

class UserRegistered extends BaseEvent
{
    public function __construct(
        public int $userId,
        public string $email
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'user.registered';
    }

    public function getPayload(): array
    {
        return [
            'user_id' => $this->userId,
            'email' => $this->email,
        ];
    }
    public function getEntityType(): string
    {
        return 'user';
    }

    public function getEntityId(): int|string|null
    {
        return $this->userId;
    }
}
