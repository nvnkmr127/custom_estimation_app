<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Core\Events\DomainEvent;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;

class NotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the queued listener may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the queued listener.
     *
     * @var int
     */
    public $backoff = 30;

    protected $decisionService;

    /**
     * Create the event listener.
     */
    public function __construct(
        \App\Services\Notifications\NotificationDecisionService $decisionService
    ) {
        $this->decisionService = $decisionService;
    }

    /**
     * Handle the event.
     */
    public function handle(DomainEvent $event): void
    {
        // For in-app notifications, we usually need a recipient.
        // We might need to determine the recipients based on the event type.
        $recipients = $this->determineRecipients($event);
        $actorId = $event->getTriggeredBy();
        $actor = $actorId ? \App\Models\User::find($actorId) : null;

        foreach ($recipients as $recipient) {
            $decision = $this->decisionService->evaluate($event, $recipient, $actor);

            if (!$decision->shouldNotify || !in_array('in-app', $decision->channels)) {
                continue;
            }

            // Record for deduplication
            $cacheKey = "notify_dedup:" . md5($event->getEventName() . ":" . $event->getEntityType() . ":" . $event->getEntityId() . ":" . $recipient->id);
            $cooldownMinutes = Setting::getCached("notification_cooldown_" . $event->getEventName(), 5);
            Cache::put($cacheKey, true, now()->addMinutes($cooldownMinutes));

            // Persistence: Notifications stored in the database
            \Illuminate\Support\Facades\DB::table('notifications')->insert([
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => $event->getEventName(),
                'notifiable_type' => get_class($recipient),
                'notifiable_id' => $recipient->id,
                'data' => json_encode(array_merge($event->getPayload(), [
                    'urgency' => $decision->urgency,
                    'event_id' => $event->getEventId(),
                    'subject' => $this->generateSubject($event),
                ])),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    protected function generateSubject(DomainEvent $event): string
    {
        return match ($event->getEventName()) {
            'approval.requested' => 'New Approval Request',
            'estimate.approved' => 'Estimate Approved',
            'estimate.rejected' => 'Estimate Rejected/Changes Requested',
            'comment.added' => 'New Comment on Estimate',
            'estimate.viewed' => 'Estimate Viewed by Client',
            'user.registered' => 'Welcome to the Platform',
            default => 'New Activity Notification',
        };
    }

    protected function determineRecipients(DomainEvent $event): array
    {
        // Logic to find who should be notified for this event
        // This usually depends on the event type.
        $eventName = $event->getEventName();
        $recipients = [];

        if ($event instanceof \App\Core\Events\Approvals\ApprovalRequested) {
            if ($user = \App\Models\User::find($event->approverUserId)) {
                $recipients[] = $user;
            }
        } elseif ($event instanceof \App\Core\Events\Comments\CommentAdded) {
            $comment = \App\Models\EstimateComment::find($event->commentId);
            if ($comment && $comment->type === 'client') {
                $estimate = $comment->estimate;
                if ($estimate && $estimate->creator) {
                    $recipients[] = $estimate->creator;
                }
            }
        } elseif ($event instanceof \App\Core\Events\Estimates\EstimateApproved) {
            if ($event->approvalType === 'internal') {
                $estimate = $event->estimate;
                if ($estimate && $estimate->creator) {
                    $recipients[] = $estimate->creator;
                }
            }
        } elseif ($eventName === 'estimate.rejected' || $eventName === 'estimate.request_changes') {
            $estimateId = $event->getEntityId();
            $estimate = \App\Models\Estimate::find($estimateId);
            if ($estimate && $estimate->creator) {
                $recipients[] = $estimate->creator;
            }
        }

        return $recipients;
    }

    /**
     * Handle a job failure.
     */
    public function failed(DomainEvent $event, \Throwable $exception): void
    {
        \Illuminate\Support\Facades\Log::error('NotificationListener Failed: ' . $exception->getMessage(), [
            'event_id' => $event->getEventId(),
        ]);
    }
}
