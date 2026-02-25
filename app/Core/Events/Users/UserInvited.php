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
        $inviter = \App\Models\User::find($this->inviterId);
        $user = \App\Models\User::find($this->userId);

        return [
            'user_id' => $this->userId,
            'user_name' => $user?->name ?? 'N/A',
            'user_email' => $user?->email ?? 'N/A',
            'inviter_id' => $this->inviterId,

            // Inviter Details
            'inviter_name' => $inviter?->name ?? 'N/A',
            'inviter_email' => $inviter?->email ?? 'N/A',
            'inviter_contact' => $inviter?->mobile_number ?? 'N/A',
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
