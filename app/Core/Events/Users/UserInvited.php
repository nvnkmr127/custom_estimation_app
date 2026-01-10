<?php

namespace App\Core\Events\Users;

use App\Core\Events\BaseEvent;

class UserInvited extends BaseEvent
{
    public function __construct(
        public int $userId,
        public int $inviterId
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'user.invited';
    }

    public function getPayload(): array
    {
        return [
            'user_id' => $this->userId,
            'inviter_id' => $this->inviterId,
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
