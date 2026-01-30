<?php

namespace App\Services\Notifications;

use App\Core\Events\DomainEvent;
use App\Models\User;
use App\Models\Estimate;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class NotificationDecisionService
{
    public function __construct(
        protected NotificationPreferenceService $preferenceService
    ) {
    }

    /**
     * Evaluate if a notification should be sent and via which channels.
     */
    public function evaluate(DomainEvent $event, ?User $recipient = null): NotificationDecision
    {
        $eventName = $event->getEventName();
        $actorId = $event->getTriggeredBy();

        // 1. Determine base urgency and priority
        $urgency = $this->determineUrgency($event);

        // 2. Actor vs Recipient (Self-triggered suppression)
        // Rule: If actor_id equals recipient_id -> suppress, EXCEPT critical system events
        if ($recipient && $actorId === $recipient->id && $urgency !== DomainEvent::PRIORITY_CRITICAL) {
            return NotificationDecision::suppress('Self-triggered event (Non-critical)');
        }

        // 3. Deduplication check
        if ($this->isDuplicate($event, $recipient)) {
            return NotificationDecision::suppress('Duplicate notification within cooldown period');
        }

        // 4. Potential delay based on time
        $delay = $this->calculateTimeDelay($urgency);

        // 5. Evaluate channels based on preferences (if user available)
        $channels = $this->determineChannels($event, $recipient);

        // If no channels are active, suppress
        if (empty($channels)) {
            return NotificationDecision::suppress('No channels active for this event');
        }

        // 6. Check if event should be notified at all (Preferences check)
        if ($recipient && !$this->shouldNotifyRecipient($recipient, $eventName)) {
            return NotificationDecision::suppress('User preferences explicitly muted this event');
        }

        return new NotificationDecision(
            shouldNotify: true,
            channels: $channels,
            urgency: $urgency,
            delay: $delay
        );
    }

    /**
     * Determine urgency based on event type and data (like estimate value).
     */
    protected function determineUrgency(DomainEvent $event): string
    {
        $eventName = $event->getEventName();
        $basePriority = $event->getPriority();

        // 1. Critical events (Check event type first)
        if (in_array($eventName, ['approval.requested', 'user.registered']) || $basePriority === DomainEvent::PRIORITY_CRITICAL) {
            return DomainEvent::PRIORITY_CRITICAL;
        }

        // 2. Check estimate value thresholds if applicable (Escalation logic)
        if ($event->getEntityType() === 'estimate' || method_exists($event, 'estimateId') || isset($event->estimateId)) {
            $estimateId = $event->getEntityId() ?: ($event->estimateId ?? null);
            if ($estimateId) {
                $estimate = Estimate::find($estimateId);
                if ($estimate) {
                    if ($estimate->grand_total >= 50000) {
                        return DomainEvent::PRIORITY_CRITICAL;
                    }
                    if ($estimate->grand_total >= 5000) {
                        return DomainEvent::PRIORITY_HIGH;
                    }
                }
            }
        }

        // 3. Match against other event types or fall back to event's base priority
        return match ($eventName) {
            'comment.added' => DomainEvent::PRIORITY_HIGH,
            'estimate.viewed' => DomainEvent::PRIORITY_NORMAL,
            default => $basePriority,
        };
    }

    /**
     * Calculate delay based on business hours.
     * Critical/High urgency events bypass business hours.
     * Normal/Low urgency events are delayed if triggered at night or weekends.
     */
    protected function calculateTimeDelay(string $urgency): int
    {
        if (in_array($urgency, [DomainEvent::PRIORITY_CRITICAL, DomainEvent::PRIORITY_HIGH])) {
            return 0;
        }

        $now = Carbon::now();

        // Simple business hours logic: Mon-Fri, 09:00 - 18:00
        $startHour = Setting::getCached('business_hours_start', 9);
        $endHour = Setting::getCached('business_hours_end', 18);

        $isWeekend = $now->isWeekend();
        $isNight = $now->hour < $startHour || $now->hour >= $endHour;

        if (!$isWeekend && !$isNight) {
            return 0;
        }

        // For this implementation, we just return a nominal delay or 0 if we decide to just "let it be".
        // A real delay would calculate the seconds until the next business morning.
        if ($isWeekend || $isNight) {
            $nextBusinessTime = $now->copy();

            if ($isWeekend) {
                $nextBusinessTime->nextWeekday()->setTime($startHour, 0);
            } else if ($isNight) {
                if ($now->hour >= $endHour) {
                    $nextBusinessTime->addDay();
                }
                $nextBusinessTime->setTime($startHour, 0);

                if ($nextBusinessTime->isWeekend()) {
                    $nextBusinessTime->nextWeekday()->setTime($startHour, 0);
                }
            }

            return $now->diffInSeconds($nextBusinessTime);
        }

        return 0;
    }

    /**
     * Determine which channels should be used.
     */
    protected function determineChannels(DomainEvent $event, ?User $recipient): array
    {
        $channels = [];

        // Webhooks are usually global in this app context
        $eventName = $event->getEventName();
        if (in_array($eventName, ['estimate.submitted_for_approval', 'estimate.sent'])) {
            $channels[] = 'webhook';
        }

        if ($recipient) {
            $channels[] = 'email';
            $channels[] = 'in-app';
        }

        return $channels;
    }

    /**
     * Final double check against user preferences.
     */
    protected function shouldNotifyRecipient(User $user, string $eventType): bool
    {
        // Use the existing preference service check
        $pref = $this->preferenceService->getPreference($user, $eventType);

        return $pref !== 'muted';
    }

    /**
     * Check if this notification is a duplicate within a cooldown period.
     */
    protected function isDuplicate(DomainEvent $event, ?User $recipient): bool
    {
        $eventName = $event->getEventName();
        $entityType = $event->getEntityType();
        $entityId = $event->getEntityId();
        $recipientId = $recipient ? $recipient->id : 'system';

        $cacheKey = "notify_dedup:" . md5("{$eventName}:{$entityType}:{$entityId}:{$recipientId}");

        if (Cache::has($cacheKey)) {
            return true;
        }

        // Get configurable cooldown or default to 5 minutes
        $cooldownMinutes = Setting::getCached("notification_cooldown_{$eventName}", 5);

        Cache::put($cacheKey, true, now()->addMinutes($cooldownMinutes));

        return false;
    }
}
