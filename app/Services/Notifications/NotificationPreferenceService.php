<?php

namespace App\Services\Notifications;

use App\Models\User;
use App\Models\NotificationPreference;
use App\Models\PendingNotification;

class NotificationPreferenceService
{
    /**
     * Get all available event types that can be configured.
     */
    public static function getAvailableEventTypes(): array
    {
        return [
            'approval.requested' => [
                'name' => 'Approval Requests',
                'description' => 'Get notified when an estimate requires your approval.',
                'default' => NotificationPreference::FREQUENCY_INSTANT,
            ],
            'estimate.viewed' => [
                'name' => 'Estimate Viewed',
                'description' => 'Get notified when a client views an estimate you created.',
                'default' => NotificationPreference::FREQUENCY_INSTANT,
            ],
            'comment.added' => [
                'name' => 'New Comments',
                'description' => 'Get notified when a client or team member posts a comment.',
                'default' => NotificationPreference::FREQUENCY_INSTANT,
            ],
            'user.registered' => [
                'name' => 'Account Registration',
                'description' => 'Welcome emails and account setup notifications.',
                'default' => NotificationPreference::FREQUENCY_INSTANT,
            ],
        ];
    }

    /**
     * Check if a user should receive an instant notification for a specific event.
     */
    public function shouldNotifyInstant(User $user, string $eventType, string $channel = 'email', string $priority = 'normal'): bool
    {
        // Critical events bypass digest and mute
        if ($priority === \App\Core\Events\DomainEvent::PRIORITY_CRITICAL) {
            return true;
        }

        // Low always goes to digest
        if ($priority === \App\Core\Events\DomainEvent::PRIORITY_LOW) {
            return false;
        }

        // High events trigger instant
        if ($priority === \App\Core\Events\DomainEvent::PRIORITY_HIGH) {
            return true;
        }

        $preference = $this->getPreference($user, $eventType, $channel);

        return $preference === NotificationPreference::FREQUENCY_INSTANT;
    }

    /**
     * Check if a notification should be queued for a digest.
     */
    public function isDigest(User $user, string $eventType, string $channel = 'email', string $priority = 'normal'): bool
    {
        // Critical events bypass digest
        if ($priority === \App\Core\Events\DomainEvent::PRIORITY_CRITICAL) {
            return false;
        }

        // Low always goes to digest
        if ($priority === \App\Core\Events\DomainEvent::PRIORITY_LOW) {
            return true;
        }

        $preference = $this->getPreference($user, $eventType, $channel);

        return in_array($preference, [
            NotificationPreference::FREQUENCY_DAILY,
            NotificationPreference::FREQUENCY_WEEKLY
        ]);
    }

    /**
     * Queue a notification for a digest delivery.
     */
    public function queueForDigest(User $user, string $eventType, string $template, array $payload, ?string $eventId = null, string $priority = 'normal'): void
    {
        $frequency = $this->getPreference($user, $eventType);

        // Store priority in payload for grouping in digests
        $payload['priority'] = $priority;

        PendingNotification::create([
            'user_id' => $user->id,
            'event_type' => $eventType,
            'event_id' => $eventId,
            'template' => $template,
            'payload' => $payload,
            'frequency' => $frequency,
        ]);
    }

    /**
     * Get the resolved preference for a user, event type, and channel.
     * Defaults to 'instant' if no setting exists.
     */
    public function getPreference(User $user, string $eventType, string $channel = 'email'): string
    {
        $pref = NotificationPreference::where('user_id', $user->id)
            ->where('event_type', $eventType)
            ->where('channel', $channel)
            ->first();

        if (!$pref) {
            return $this->getDefaultPreference($eventType);
        }

        return $pref->frequency;
    }

    /**
     * Provide default frequencies based on event types.
     * Some events should always be instant by default (e.g., approvals).
     */
    protected function getDefaultPreference(string $eventType): string
    {
        $instantEvents = [
            'approval.requested',
            'user.registered',
            'estimate.sent', // usually for client, so usually instant
        ];

        if (in_array($eventType, $instantEvents)) {
            return NotificationPreference::FREQUENCY_INSTANT;
        }

        return NotificationPreference::FREQUENCY_INSTANT; // Default to instant for all for now, user can mute.
    }
}
