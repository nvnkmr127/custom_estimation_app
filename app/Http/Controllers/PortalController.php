<?php

namespace App\Http\Controllers;

use App\Models\Estimate;
use App\Models\ChangeRequestChecklist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class PortalController extends Controller
{
    // protected $perfexApi; // Removed unused property

    protected $analytics;
    protected $dispatcher;
    protected $stateService;

    public function __construct(
        \App\Services\AnalyticsService $analytics,
        \App\Core\Events\EventDispatcherInterface $dispatcher,
        \App\Services\Estimates\EstimateStateService $stateService
    ) {
        $this->analytics = $analytics;
        $this->dispatcher = $dispatcher;
        $this->stateService = $stateService;
    }

    /**
     * Centralized validation for portal access.
     */
    protected function validateAccess(Estimate $estimate, Request $request)
    {
        // 1. Signature Verification (Laravel built-in)
        if (!$request->hasValidSignature()) {
            abort(403, 'This link has expired or is invalid.');
        }

        // 2. Version Verification
        if (!$estimate->is_current_version) {
            // Security: Use a generic error message to avoid confirming existence of historical versions
            abort(403, 'This estimate link is no longer active. Please check your email for the latest updated version.');
        }

        // 3. Status Verification (Internal Lifecycle)
        if ($estimate->estimate_status === Estimate::EST_STATUS_DECLINED) {
            abort(410, 'This estimate has been declined or voided.');
        }

        // 4. Client Lifecycle Status Verification
        $allowedStatuses = [
            Estimate::CLT_STATUS_SENT,
            Estimate::CLT_STATUS_VIEWED,
            Estimate::CLT_STATUS_ACCEPTED,
            Estimate::CLT_STATUS_DECLINED,
        ];

        // Check estimate_status if client_status is somehow not reliable
        if (!$estimate->client_status && !in_array($estimate->estimate_status, [Estimate::EST_STATUS_SENT, Estimate::EST_STATUS_APPROVED, Estimate::EST_STATUS_ACCEPTED, Estimate::EST_STATUS_DECLINED])) {
            abort(403, 'This estimate is not yet available for viewing.');
        }

        if ($estimate->client_status && !in_array($estimate->client_status, $allowedStatuses)) {
            // If it was rejected or draft internally, block client access
            abort(403, 'This estimate is currently unavailable.');
        }

        // 5. Expiration Verification (Business Logic)
        if ($estimate->isExpired()) {
            // We allow viewing if already accepted, but block new actions
            if ($request->isMethod('get')) {
                // Allow viewing (blade shows countdown/expired state)
            } else {
                abort(403, 'This estimate has expired and can no longer be acted upon.');
            }
        }
    }

    /**
     * Display the specified estimate to the client.
     */
    public function show(Request $request, Estimate $estimate)
    {
        $this->validateAccess($estimate, $request);

        // Track view
        $this->analytics->logAccess($estimate, 'view');
        $estimate->increment('view_count');
        $estimate->update(['last_viewed_at' => now()]);

        \App\Models\ActivityLog::log('proposal_viewed', $estimate, "Proposal for Estimate #{$estimate->estimate_number} was viewed by the client.");

        // Dispatch Event
        $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateViewed($estimate, null, $request->ip()));

        $estimate->increment('engagement_score'); // Boost score on every view

        // Load items for the view
        $estimate->load([
            'sections.items.comments.user',
            'sections.items.comments.replies', // Load replies for item comments
            'comments.user',
            'comments.replies' // Load replies for estimate comments
        ]);

        // Render PDF Template HTML
        $template = $estimate->pdfTemplate ?? \App\Models\PdfTemplate::where('is_active', true)->where('is_default', true)->first() ?? \App\Models\PdfTemplate::first();

        $htmlContent = '';
        if ($template) {
            $service = new \App\Services\PdfRenderingService;
            $htmlContent = $service->render($template, $estimate, true);
        }

        // Prepare unified comments logic
        $commentData = collect();

        \Log::info("Portal View: Processing Family Comments for Estimate Family of #{$estimate->id}");

        $transform = function ($c, $itemName = null, $parent = null) {
            // Prioritize parent context effectively to group replies with their thread
            $type = ($parent && $parent->commentable_type) ? $parent->commentable_type : $c->commentable_type;
            $id = ($parent && $parent->commentable_id) ? $parent->commentable_id : $c->commentable_id;

            $finalItemName = $itemName ?? (($type === 'App\Models\EstimateItem' && $c->commentable) ? ($c->commentable->name ?? optional(\App\Models\EstimateItem::find($id))->name) : null);

            // Security: Strictly whitelist displayed user fields to prevent Staff PII leakage (email, mobileID)
            $user = null;
            if ($c->user) {
                $user = [
                    'name' => $c->user->name,
                    'role_badge_class' => $c->user->role_badge_class ?? 'staff',
                ];
            }

            return [
                'id' => $c->id,
                'comment' => $c->comment,
                'type' => $c->type, // 'client' or 'internal' (staff)
                'created_at' => $c->created_at,
                'commentable_type' => $type,
                'commentable_id' => $id,
                'user' => $user,
                'item_name' => $finalItemName
            ];
        };

        // Fetch all comments in the family
        $familyComments = $estimate->allFamilyComments()->get();

        foreach ($familyComments as $comment) {
            // Strictly exclude internal comments from the portal view
            if ($comment->type === 'internal') {
                continue;
            }
            $commentData->push($transform($comment, null, null));
        }

        $comments = $commentData->filter()->unique('id')->sortBy('created_at')->values();
        $checklists = ChangeRequestChecklist::getAllCached();

        return view('portal.estimates.show', compact('estimate', 'htmlContent', 'comments', 'checklists'));
    }

    /**
     * Mark the estimate as accepted.
     */
    public function accept(Request $request, Estimate $estimate)
    {
        $this->validateAccess($estimate, $request);

        $request->validate([
            'signature' => 'required|string',
        ]);

        if (!$estimate->canBeAccepted()) {
            return redirect()->back()->with('error', 'This estimate cannot be accepted. It may be expired, not yet sent, or already processed.');
        }

        // Capture Location (Simple Lookup)
        $location = null;
        try {
            $ip = $request->ip();
            if ($ip !== '127.0.0.1' && $ip !== '::1') {
                $response = \Illuminate\Support\Facades\Http::timeout(3)->get("https://ipapi.co/{$ip}/json/");
                if ($response->successful()) {
                    $data = $response->json();
                    if ($data && !isset($data['error'])) {
                        $location = ($data['city'] ?? '') . ', ' . ($data['region'] ?? '') . ', ' . ($data['country_name'] ?? '');
                        $location = trim($location, ', ');
                    }
                }
            } else {
                $location = 'Localhost';
            }
        } catch (\Exception $e) {
            // Ignore location errors
        }

        try {
            return DB::transaction(function () use ($estimate, $request, $location) {
                // Use State Service for Transition (Handles Lock, Concurrency, and Side Effects)
                $this->stateService->transitionClientStatus($estimate, Estimate::CLT_STATUS_ACCEPTED, false, [
                    'signature' => $request->signature,
                    'signed_at' => now(),
                    'signer_ip' => $request->ip(),
                    'signer_agent' => $request->userAgent(),
                    'signer_location' => $location,
                ]);

                // Sync will now happen via PerfexSyncListener on the EstimateAccepted event below

                // Notify Admins
                $admins = \App\Models\User::whereIn('role', ['super_admin', 'estimator_admin', 'admin'])->get();
                \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\EstimateStatusUpdated($estimate, 'accepted'));

                // Dispatch Event
                $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateAccepted($estimate, 0, 'client'));

                return redirect()->back()->with('success', 'Thank you! You have successfully signed and accepted the estimate. Our team has been notified.');
            });

        } catch (\InvalidArgumentException $e) {
            if ($estimate->client_status === Estimate::CLT_STATUS_ACCEPTED) {
                return redirect()->back()->with('info', 'This estimate has already been accepted.');
            }
            return redirect()->back()->with('error', "This estimate cannot be accepted: " . $e->getMessage());
        } catch (\Exception $e) {
            \Log::error("Failed to accept estimate #{$estimate->id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'An unexpected error occurred while processing your acceptance.');
        }
    }

    /**
     * Mark the estimate as declined.
     */
    public function decline(Request $request, Estimate $estimate)
    {
        $this->validateAccess($estimate, $request);

        $request->validate([
            'client_notes' => 'required|string|max:1000',
        ]);

        try {
            return DB::transaction(function () use ($estimate, $request) {
                $this->stateService->transitionClientStatus($estimate, Estimate::CLT_STATUS_DECLINED, false, [
                    'client_notes' => $request->client_notes,
                ]);

                // Notify Admins
                $admins = \App\Models\User::whereIn('role', ['super_admin', 'estimator_admin', 'admin'])->get();
                \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\EstimateStatusUpdated($estimate, 'declined', $request->client_notes));

                // Dispatch Event
                $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateDeclined($estimate, 0, $request->client_notes));

                return redirect()->back()->with('success', 'You have declined the estimate. Thank you for your feedback.');
            });
        } catch (\InvalidArgumentException $e) {
            if ($estimate->client_status === Estimate::CLT_STATUS_DECLINED) {
                return redirect()->back()->with('info', 'This estimate has already been declined.');
            }
            return redirect()->back()->with('error', "This estimate cannot be declined: " . $e->getMessage());
        } catch (\Exception $e) {
            \Log::error("Failed to decline estimate #{$estimate->id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'An unexpected error occurred.');
        }
    }
    /**
     * Store a comment from the client.
     */
    public function comment(Request $request, Estimate $estimate)
    {
        \Log::info("PortalController: New comment attempt on Estimate #{$estimate->id}", $request->all());
        $this->validateAccess($estimate, $request);

        $request->validate([
            'comment' => 'required|string|max:1000',
            'client_name' => 'nullable|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'item_id' => 'nullable|exists:estimate_items,id',
        ]);

        $commentableType = \App\Models\Estimate::class;
        $commentableId = $estimate->id;

        if ($request->filled('item_id')) {
            // Verify item belongs to estimate
            $item = $estimate->items()->where('id', $request->item_id)->first();
            // If direct items relationship doesn't cover sections (it depends on implementation), fallback
            if (!$item) {
                // Try sections items
                foreach ($estimate->sections as $section) {
                    if ($section->items->contains('id', $request->item_id)) {
                        $item = $section->items->where('id', $request->item_id)->first();
                        break;
                    }
                }
            }

            if ($item) {
                $commentableType = \App\Models\EstimateItem::class;
                $commentableId = $item->id;
            }
        }

        $comment = $estimate->comments()->create([
            'comment' => $request->comment,
            'client_name' => $request->client_name,
            'client_email' => $request->client_email,
            'type' => 'client',
            'is_read' => false,
            'commentable_type' => $commentableType,
            'commentable_id' => $commentableId,
        ]);

        // Notify Creator and Followers
        $followers = $estimate->followers;
        
        // Also ensure all admins are notified of client comments
        $admins = \App\Models\User::whereIn('role', ['super_admin', 'estimator_admin', 'sales_manager'])->get();
        $followers = $followers->merge($admins)->unique('id');

        foreach ($followers as $follower) {
            try {
                $follower->notify(new \App\Notifications\EstimateCommentNotification($comment, $estimate));
            } catch (\Exception $e) {
                \Log::error("Failed to notify user {$follower->id} of comment: " . $e->getMessage());
            }
        }

        // Log Activity
        \App\Models\ActivityLog::log('client_commented', $estimate, "Client left a comment on Proposal #{$estimate->estimate_number}");

        // Dispatch Event
        $dispatcher = app(\App\Core\Events\EventDispatcherInterface::class);
        $dispatcher->dispatch(new \App\Core\Events\Comments\CommentAdded(
            $comment->id,
            $estimate->id,
            null, // user_id is null for clients
            substr($comment->comment, 0, 100)
        ));

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'comment' => [
                    'id' => $comment->id,
                    'comment' => $comment->comment,
                    'type' => $comment->type,
                    'created_at' => $comment->created_at->toISOString(),
                    'formatted_date' => 'Just now',
                    'client_name' => $comment->client_name ?: 'You',
                ]
            ]);
        }

        return back()->with('success', 'Thank you! Your comment has been sent to the team.');
    }

    /**
     * Store a change request with a checklist from the client.
     */
    public function requestChanges(Request $request, Estimate $estimate)
    {
        $this->validateAccess($estimate, $request);

        $request->validate([
            'comments' => 'nullable|string|max:1000',
            'checklist' => 'nullable|array',
            'client_name' => 'nullable|string|max:255',
            'client_email' => 'nullable|email|max:255',
        ]);

        $changeSummary = "";
        if ($request->filled('checklist')) {
            $tasks = ChangeRequestChecklist::whereIn('id', $request->checklist)->pluck('task')->toArray();
            $changeSummary = "Change Request Checklist:\n- " . implode("\n- ", $tasks) . "\n\n";
        }

        $commentText = $changeSummary . ($request->comments ?? 'User requested changes via portal.');

        $comment = $estimate->comments()->create([
            'comment' => $commentText,
            'client_name' => $request->client_name ?: 'Client',
            'client_email' => $request->client_email,
            'type' => 'client',
            'is_read' => false,
            'commentable_type' => Estimate::class,
            'commentable_id' => $estimate->id,
        ]);

        // Notify followers/admins
        $followers = $estimate->followers;
        $admins = \App\Models\User::whereIn('role', ['super_admin', 'estimator_admin', 'sales_manager'])->get();
        $followers = $followers->merge($admins)->unique('id');

        foreach ($followers as $follower) {
            try {
                $follower->notify(new \App\Notifications\EstimateCommentNotification($comment, $estimate));
            } catch (\Exception $e) {
                \Log::error("Failed to notify user {$follower->id} of change request: " . $e->getMessage());
            }
        }

        \App\Models\ActivityLog::log('client_requested_changes', $estimate, "Client requested changes on Proposal #{$estimate->estimate_number}");

        return back()->with('success', 'Your change request has been received. Our team will review it and update the estimate accordingly.');
    }
    /**
     * Handle "Request Call" action from client.
     */
    public function requestCall(Request $request, Estimate $estimate)
    {
        $this->validateAccess($estimate, $request);

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

    /**
     * Download the estimate PDF.
     */
    public function download(Request $request, Estimate $estimate)
    {
        $this->validateAccess($estimate, $request);

        // Reuse PDF Service
        $template = $estimate->pdfTemplate ?? \App\Models\PdfTemplate::where('is_active', true)->where('is_default', true)->first();

        if (!$template) {
            // Fallback
            $template = \App\Models\PdfTemplate::first();
        }

        if (!$template) {
            abort(404, 'PDF Template not found.');
        }

        $service = new \App\Services\PdfRenderingService;
        ini_set('memory_limit', '512M');
        $path = $service->renderAndCache($template, $estimate);

        if (!$path || !file_exists($path)) {
            abort(500, 'Failed to generate PDF.');
        }

        $this->analytics->logAccess($estimate, 'download');

        return response()->download($path, "Estimate-{$estimate->estimate_number}.pdf");
    }
}
