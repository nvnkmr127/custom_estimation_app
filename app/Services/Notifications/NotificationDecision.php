<?php

namespace App\Services\Notifications;

class NotificationDecision
{
    /**
     * @param bool $shouldNotify
     * @param array $channels
     * @param string $urgency
     * @param int $delay Delay in seconds
     */
    public function __construct(
        public bool $shouldNotify,
        public array $channels = [],
        public string $urgency = 'normal',
        public int $delay = 0,
        public ?string $reason = null
    ) {
    }

    public static function suppress(string $reason): self
    {
        return new self(false, [], 'normal', 0, $reason);
    }

    public static function notify(array $channels, string $urgency = 'normal', int $delay = 0): self
    {
        return new self(true, $channels, $urgency, $delay);
    }
}
