<?php

namespace App\Core\Events\Users;

use App\Core\Events\BaseEvent;

class UserLoggedIn extends BaseEvent
{
    public function __construct(
        public int $userId,
        public ?string $ipAddress = null
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'user.logged_in';
    }

    public function getPayload(): array
    {
        return [
            'user_id' => $this->userId,
            'ip_address' => $this->ipAddress,
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
