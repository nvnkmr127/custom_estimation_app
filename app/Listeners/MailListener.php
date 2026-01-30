<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Core\Events\DomainEvent;

class MailListener implements ShouldQueue
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
    public $backoff = 60; // 1 minute

    /**
     * Create the event listener.
     */
    protected $emailDispatcher;
    protected $preferenceService;
    protected $decisionService;

    /**
     * Create the event listener.
     */
    public function __construct(
        \App\Services\Mail\EmailDispatcher $emailDispatcher,
        \App\Services\Notifications\NotificationPreferenceService $preferenceService,
        \App\Services\Notifications\NotificationDecisionService $decisionService
    ) {
        $this->emailDispatcher = $emailDispatcher;
        $this->preferenceService = $preferenceService;
        $this->decisionService = $decisionService;
    }

    /**
     * Handle the event.
     */
    public function handle(DomainEvent $event): void
    {
        if ($event instanceof \App\Core\Events\Approvals\ApprovalRequested) {
            $this->handleApprovalRequested($event);
        } elseif ($event instanceof \App\Core\Events\Estimates\EstimateSent) {
            $this->handleEstimateSent($event);
        } elseif ($event instanceof \App\Core\Events\Users\UserRegistered) {
            $this->handleUserRegistered($event);
        } elseif ($event instanceof \App\Core\Events\Comments\CommentAdded) {
            $this->handleCommentAdded($event);
        } elseif ($event instanceof \App\Core\Events\Estimates\EstimateViewed) {
            $this->handleEstimateViewed($event);
        }
    }

    protected function handleApprovalRequested($event)
    {
        $estimate = \App\Models\Estimate::find($event->estimateId);
        $approver = \App\Models\User::find($event->approverUserId);

        if ($estimate && $approver) {
            $this->notifyUser(
                $approver,
                'approval.requested',
                'Action Required: Approval for Estimate #' . $estimate->estimate_number,
                'emails.approvals.requested',
                [
                    'approver_name' => $approver->name,
                    'estimate_number' => $estimate->estimate_number,
                    'total_amount' => number_format($estimate->grand_total, 2) . ' ' . $estimate->currency,
                    'requested_by' => $estimate->creator->name ?? 'System',
                    'view_url' => route('estimates.show', $estimate->id),
                ],
                $event->eventId,
                $event
            );
        }
    }

    protected function handleEstimateSent($event)
    {
        $estimate = $event->estimate;
        if ($estimate && $estimate->client && $estimate->client->email) {
            // Clients don't have accounts, so we always send instant emails for now.
            $this->emailDispatcher->dispatch(
                $estimate->client->email,
                'New Estimate from ' . config('app.name') . ': #' . $estimate->estimate_number,
                'emails.estimates.sent',
                [
                    'notifiable_name' => $estimate->client->name,
                    'estimate_number' => $estimate->estimate_number,
                    'total_amount' => number_format($estimate->grand_total, 2) . ' ' . $estimate->currency,
                    'view_url' => $estimate->public_url,
                    'pixel_url' => route('tracking.pixel', $estimate->id),
                ]
            );
        }
    }

    protected function handleUserRegistered($event)
    {
        $user = \App\Models\User::find($event->userId);
        if ($user) {
            $this->notifyUser(
                $user,
                'user.registered',
                'Welcome to ' . config('app.name'),
                'emails.welcome',
                ['name' => $user->name],
                $event->eventId,
                $event
            );
        }
    }

    protected function handleCommentAdded($event)
    {
        $comment = \App\Models\EstimateComment::find($event->commentId);
        if (!$comment)
            return;

        if ($comment->type === 'client') {
            $estimate = $comment->estimate;
            if ($estimate && $estimate->creator) {
                $this->notifyUser(
                    $estimate->creator,
                    'comment.added',
                    'New Comment on Estimate #' . $estimate->estimate_number,
                    'emails.comments.notification',
                    [
                        'recipient_name' => $estimate->creator->name,
                        'comment_author' => $comment->client_name ?? 'Client',
                        'estimate_number' => $estimate->estimate_number,
                        'comment_content' => $comment->comment,
                        'view_url' => route('estimates.show', $estimate->id),
                    ],
                    $event->eventId,
                    $event
                );
            }
        }
    }

    protected function handleEstimateViewed($event)
    {
        $estimate = \App\Models\Estimate::find($event->estimateId);

        if (!$estimate) {
            return;
        }

        if ($event->viewerId && $estimate->created_by === $event->viewerId) {
            return;
        }

        if ($estimate && $estimate->creator) {
            $this->notifyUser(
                $estimate->creator,
                'estimate.viewed',
                'Estimate Viewed: #' . $estimate->estimate_number,
                'emails.estimates.viewed_notification',
                [
                    'recipient_name' => $estimate->creator->name,
                    'estimate_number' => $estimate->estimate_number,
                    'view_time' => now()->format('M d, Y h:i A'),
                    'ip_address' => $event->ipAddress ?? 'Unknown',
                    'view_url' => route('estimates.show', $estimate->id),
                    'client_name' => $estimate->client->name ?? 'Client',
                ],
                $event->eventId,
                $event
            );
        }
    }

    /**
     * Centralized notification dispatcher that respects user preferences.
     */
    protected function notifyUser(
        \App\Models\User $user,
        string $eventType,
        string $subject,
        string $template,
        array $data,
        ?string $eventId = null,
        ?DomainEvent $event = null
    ): void {
        // Use NotificationDecisionService to evaluate
        if ($event) {
            $decision = $this->decisionService->evaluate($event, $user);
            if (!$decision->shouldNotify || !in_array('email', $decision->channels)) {
                return;
            }

            // Handle delay if any
            if ($decision->delay > 0 && !app()->environment('testing')) {
                // In a real app, we might re-queue with a delay.
                // For this implementation, we'll just log it or let it pass if it's not a background job.
                \Illuminate\Support\Facades\Log::info("Notification delayed by {$decision->delay}s for event {$eventType}");
            }

            // Inject context for conversion tracking
            $data['event_type'] = $event->getEventName();
            $data['entity_type'] = $event->getEntityType();
            $data['entity_id'] = $event->getEntityId();
        }

        $priority = $event ? $event->getPriority() : \App\Core\Events\DomainEvent::PRIORITY_NORMAL;

        if ($this->preferenceService->shouldNotifyInstant($user, $eventType, 'email', $priority)) {
            $this->emailDispatcher->dispatch($user->email, $subject, $template, $data, null, $priority);
            return;
        }

        if ($this->preferenceService->isDigest($user, $eventType, 'email', $priority)) {
            $data['subject'] = $subject;
            $this->preferenceService->queueForDigest($user, $eventType, $template, $data, $eventId, $priority);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(DomainEvent $event, \Throwable $exception): void
    {
        // Log failure
        \Illuminate\Support\Facades\Log::error('MailListener Failed: ' . $exception->getMessage(), [
            'event_id' => $event->getEventId(),
            'error' => $exception->getTraceAsString(),
        ]);

        // Optional: Update EventLog status if needed, but beware of overwriting success from other listeners.
        // For visibility, we might just log for now or dispatch a SystemJobFailed event if using the old approach,
        // but simple logging is best for "Admin must see" via logs/failed_jobs table.
    }
}
