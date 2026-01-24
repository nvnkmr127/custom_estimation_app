<?php

namespace App\Http\Controllers;

use App\Models\Estimate;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    protected $perfexApi;

    protected $analytics;
    protected $dispatcher;

    public function __construct(\App\Services\PerfexApiService $perfexApi, \App\Services\AnalyticsService $analytics, \App\Core\Events\EventDispatcherInterface $dispatcher)
    {
        $this->perfexApi = $perfexApi;
        $this->analytics = $analytics;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Display the specified estimate to the client.
     */
    public function show(Request $request, Estimate $estimate)
    {
        // Ensure the signed URL is valid
        if (!$request->hasValidSignature()) {
            abort(403, 'This link has expired or is invalid.');
        }

        // Restrict access based on status
        $allowedStatuses = [
            Estimate::STATUS_SENT,
            Estimate::STATUS_ACCEPTED,
            Estimate::STATUS_DECLINED,
            Estimate::STATUS_EXPIRED
        ];

        if (!in_array($estimate->status, $allowedStatuses)) {
            abort(403, 'This estimate is not yet available for viewing.');
        }

        // Track view
        $this->analytics->logAccess($estimate, 'view');
        $estimate->increment('view_count');
        $estimate->update(['last_viewed_at' => now()]);

        \App\Models\ActivityLog::log('proposal_viewed', $estimate, "Proposal for Estimate #{$estimate->estimate_number} was viewed by the client.");

        // Dispatch Event
        $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateViewed($estimate->id, null, $request->ip()));

        // --- Hot Lead Detection ---
        $hotLeadThreshold = \App\Models\Setting::getCached('nurture_hot_lead_threshold', 3);
        $estimate->increment('engagement_score'); // Boost score on every view

        // Check velocity (views in last hour)
        $recentViews = \App\Models\ActivityLog::where('subject_type', Estimate::class)
            ->where('subject_id', $estimate->id)
            ->where('action', 'proposal_viewed')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentViews >= $hotLeadThreshold) {
            // Avoid spamming if already alerted recently (e.g., in last hour) could be better, but for now strict alert.
            // Only alert if we haven't alerted for this velocity burst?
            // Simple mechanism: Check if we sent an alert in last hour.
            // We don't have an easy way to check notifications sent.
            // Let's rely on the threshold.

            foreach ($estimate->followers as $follower) {
                // Optimization: Queue this check/send
                $follower->notify(new \App\Notifications\HotLeadAlert($estimate, [
                    'reason' => "High Velocity: Viewed {$recentViews} times in the last hour."
                ]));
            }
        }
        // ---------------------------

        // --- Smart Nudge Logic (Moved from Pixel Tracking) ---
        $viewCount = $estimate->view_count;
        $nudgeThreshold = config('estimation.nudge_threshold', 3);

        if (
            $viewCount >= $nudgeThreshold
            && !in_array($estimate->status, ['accepted', 'declined', 'expired'])
            && !$estimate->nudge_task_created
        ) {
            $relId = $estimate->perfex_proposal_id;

            if ($relId) {
                // We use a job to avoid blocking the user view
                // For now, doing it inline as per previous logic, but robustly
                try {
                    $description = "Smart Nudge: Client has viewed Estimate #{$estimate->estimate_number} {$viewCount} times on the portal but not accepted.";
                    $taskData = [
                        'name' => 'Follow up on Estimate #' . $estimate->estimate_number,
                        'description' => $description,
                        'priority' => 3, // Medium/High
                        'startdate' => now()->format('Y-m-d'),
                        'rel_type' => 'proposal',
                        'rel_id' => $relId,
                    ];

                    $response = $this->perfexApi->createTask($taskData); // Using injected service

                    if (isset($response['id']) || (isset($response['status']) && $response['status'] == true)) {
                        $estimate->update(['nudge_task_created' => true]);
                        \App\Models\ActivityLog::log('system_action', $estimate, 'Smart Nudge: CRM Task Created for follow-up.');
                    } else {
                        \Illuminate\Support\Facades\Log::warning('Smart Nudge Task Failed: ' . json_encode($response));
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Smart Nudge Exception: ' . $e->getMessage());
                }
            }
        }
        // ---------------------------

        // Load items for the view
        $estimate->load(['sections.items', 'comments.user']); // Load comments with user (if any internal replies are visible, though currently filtering for client)

        // Render PDF Template HTML
        $template = $estimate->pdfTemplate ?? \App\Models\PdfTemplate::where('is_active', true)->where('is_default', true)->first() ?? \App\Models\PdfTemplate::first();

        $htmlContent = '';
        if ($template) {
            $service = new \App\Services\PdfRenderingService;
            $htmlContent = $service->render($template, $estimate);
        }

        return view('portal.estimates.show', compact('estimate', 'htmlContent'));
    }

    /**
     * Mark the estimate as accepted.
     */
    public function accept(Request $request, Estimate $estimate)
    {
        if (!$request->hasValidSignature()) {
            abort(403);
        }

        $request->validate([
            'signature' => 'required|string',
        ]);

        if ($estimate->status === 'accepted') {
            return redirect()->back()->with('info', 'This estimate has already been accepted.');
        }

        // Capture Location (Simple Lookup)
        $location = null;
        try {
            $ip = $request->ip();
            if ($ip !== '127.0.0.1' && $ip !== '::1') {
                $json = @file_get_contents("http://ip-api.com/json/{$ip}?fields=city,country,regionName", false, stream_context_create(['http' => ['timeout' => 2]]));
                if ($json) {
                    $data = json_decode($json, true);
                    if ($data && ($data['status'] ?? '') === 'success') {
                        $location = ($data['city'] ?? '') . ', ' . ($data['regionName'] ?? '') . ', ' . ($data['country'] ?? '');
                        $location = trim($location, ', ');
                    }
                }
            } else {
                $location = 'Localhost';
            }
        } catch (\Exception $e) {
            // Ignore location errors
        }

        $estimate->update([
            'status' => 'accepted',
            'signature' => $request->signature,
            'signed_at' => now(),
            'signer_ip' => $request->ip(),
            'signer_agent' => $request->userAgent(),
            'signer_location' => $location,
        ]);

        // Auto-Sync to Perfex
        $this->perfexApi->syncEstimate($estimate);

        // Notify Admins
        $admins = \App\Models\User::whereIn('role', ['super_admin', 'estimator_admin'])->get();
        \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\EstimateStatusUpdated($estimate, 'accepted'));

        // Dispatch Event
        // We use 'client' as approver_id? But approver_id expects int. 
        // For client, we might need a special ID or update the event to allow null?
        // Proposal says `approverId` is `int`.
        // If it's a client, they are not a user.
        // I should probably use 0 or a negative number for client? Or update the event to nullable.
        // Let's check EstimateApproved definition.
        // public int $approverId.
        // I will use 0 for now to indicate "External/Client". Alternatively, if the client has a user account (some systems do), use that.
        // Here, it seems clients don't log in to portal (signed url).
        $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateApproved($estimate->id, 0, 'client'));

        return redirect()->back()->with('success', 'Thank you! You have successfully signed and accepted the estimate. It has also been synced to our CRM.');
    }

    /**
     * Mark the estimate as declined.
     */
    public function decline(Request $request, Estimate $estimate)
    {
        if (!$request->hasValidSignature()) {
            abort(403);
        }

        $request->validate([
            'client_notes' => 'required|string|max:1000',
        ]);

        $estimate->update([
            'status' => 'declined',
            'client_notes' => $request->client_notes,
        ]);

        // Notify Admins
        $admins = \App\Models\User::whereIn('role', ['super_admin', 'estimator_admin'])->get();
        \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\EstimateStatusUpdated($estimate, 'declined'));

        // Dispatch Event
        $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateDeclined($estimate->id, 0, $request->client_notes));

        return redirect()->back()->with('success', 'You have declined the estimate. Thank you for your feedback.');
    }
    /**
     * Store a comment from the client.
     */
    public function comment(Request $request, Estimate $estimate)
    {
        if (!$request->hasValidSignature()) {
            abort(403);
        }

        $request->validate([
            'comment' => 'required|string|max:1000',
            'client_name' => 'nullable|string|max:255',
            'client_email' => 'nullable|email|max:255',
        ]);

        $comment = $estimate->comments()->create([
            'comment' => $request->comment,
            'client_name' => $request->client_name,
            'client_email' => $request->client_email,
            'type' => 'client',
            'is_read' => false,
            'commentable_type' => \App\Models\Estimate::class,
            'commentable_id' => $estimate->id,
        ]);

        // Notify Creator and Followers
        $followers = $estimate->followers;
        foreach ($followers as $follower) {
            $follower->notify(new \App\Notifications\EstimateCommentNotification($comment, $estimate));
        }

        return back()->with('success', 'Thank you! Your comment has been sent to the team.');
    }
    /**
     * Handle "Request Call" action from client.
     */
    public function requestCall(Request $request, Estimate $estimate)
    {
        if (!$request->hasValidSignature()) {
            abort(403);
        }

        // Notify Creator and Followers
        $followers = $estimate->followers;
        foreach ($followers as $follower) {
            $follower->notify(new \App\Notifications\HotLeadAlert($estimate, [
                'reason' => "Client requested a call immediately.",
                'urgent' => true
            ]));
        }

        // Also log activity
        \App\Models\ActivityLog::log('call_requested', $estimate, "Client requested an immediate call.");

        return back()->with('success', 'Request received! We will call you shortly.');
    }
}
