<?php

namespace App\Http\Controllers;

use App\Models\Estimate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PortalController extends Controller
{
    protected $perfexApi;

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
            // Find the current version if possible to redirect, or just block
            $current = $estimate->parent_id ? Estimate::where('id', $estimate->parent_id)->first() : Estimate::where('parent_id', $estimate->id)->where('is_current_version', true)->first();
            if ($current && $current->id !== $estimate->id) {
                // We don't automatically redirect to avoid exposing new version URLs without a new email,
                // but we block the old one.
                abort(403, 'This version of the estimate is no longer active. Please check your email for the latest version.');
            }
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

        \Log::info("Portal View: Processing Comments for Estimate #{$estimate->id}");

        $transform = function ($c, $itemName = null, $parent = null) {
            // Prioritize parent context effectively to group replies with their thread
            $type = ($parent && $parent->commentable_type) ? $parent->commentable_type : $c->commentable_type;
            $id = ($parent && $parent->commentable_id) ? $parent->commentable_id : $c->commentable_id;

            $finalItemName = $itemName ?? (($type === 'App\Models\EstimateItem' && $c->commentable) ? ($c->commentable->name ?? optional(\App\Models\EstimateItem::find($id))->name) : null);

            \Log::info("Processing Comment ID: {$c->id}, Type: {$c->type}, ParentID: " . ($parent ? $parent->id : 'NULL') . ", ItemName: {$finalItemName}, CommentableType: {$type}, CommentableID: {$id}");

            return [
                'id' => $c->id,
                'comment' => $c->comment,
                'type' => $c->type,
                'created_at' => $c->created_at,
                'commentable_type' => $type,
                'commentable_id' => $id,
                'user' => $c->user,
                'item_name' => $finalItemName
            ];
        };

        // 1. Item Comments & Replies (Process these first to ensure correct context wins)
        foreach ($estimate->sections as $section) {
            foreach ($section->items as $item) {
                // We access the relationship loaded via eager loading
                foreach ($item->comments as $comment) {
                    if ($comment->type === 'internal') {
                        continue;
                    }

                    $itemName = $item->name;
                    $commentData->push($transform($comment, $itemName, null));
                    foreach ($comment->replies as $reply) {
                        if ($reply->type === 'internal') {
                            continue;
                        }
                        // Pass parent and item name
                        $commentData->push($transform($reply, $itemName, $comment));
                    }
                }
            }
        }

        // 2. General Estimate Comments & Replies
        foreach ($estimate->comments as $comment) {
            if ($comment->type === 'internal') {
                continue;
            }

            $commentData->push($transform($comment, null, null));
            foreach ($comment->replies as $reply) {
                if ($reply->type === 'internal') {
                    continue;
                }
                // Pass parent so reply inherits context
                $commentData->push($transform($reply, null, $comment));
            }
        }

        $comments = $commentData->filter()->unique('id')->sortBy('created_at')->values();

        return view('portal.estimates.show', compact('estimate', 'htmlContent', 'comments'));
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

                return redirect()->back()->with('success', 'Thank you! You have successfully signed and accepted the estimate. It has also been synced to our CRM.');
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
        foreach ($followers as $follower) {
            $follower->notify(new \App\Notifications\EstimateCommentNotification($comment, $estimate));
        }

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
