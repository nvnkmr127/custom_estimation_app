<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ApprovalChain;
use App\Models\ApprovalChecklist;
use App\Models\Client;
use App\Models\DeclineReason;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\ItemPackage;
use App\Models\PdfTemplate;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\RoomTemplate;
use App\Models\Setting;
use App\Services\AnalyticsService;
use App\Services\EstimateService;
use App\Services\PdfRenderingService;
use App\Models\UnitType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstimateController extends Controller
{
    protected $estimateService;
    protected $dispatcher;

    public function __construct(EstimateService $estimateService, \App\Core\Events\EventDispatcherInterface $dispatcher)
    {
        $this->estimateService = $estimateService;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Display a listing of estimates.
     */
    /**
     * Display a listing of estimates.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Estimate::class);

        $query = Estimate::with(['client', 'sections', 'creator'])->current()->latest();

        if (!auth()->user()->hasRole(['super_admin', 'admin', 'estimator_admin'])) {
            $query->where(function ($q) {
                $q->where('created_by', auth()->id())
                    ->orWhereHas('manualFollowers', function ($f) {
                        $f->where('user_id', auth()->id());
                    })
                    ->orWhereHas('approvals', function ($a) {
                        $a->where('user_id', auth()->id());
                    });
            });
        }

        // --- Filters ---
        if ($request->has('status') && $request->status !== 'all' && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('estimate_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('estimate_date', '<=', $request->date_to);
        }

        if ($request->filled('amount_min')) {
            $query->where('grand_total', '>=', $request->amount_min);
        }

        if ($request->filled('amount_max')) {
            $query->where('grand_total', '<=', $request->amount_max);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('estimate_number', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    });
            });
        }


        // --- Analytics (Calculated on the filtered set) ---
        $analyticsQuery = clone $query;
        $analyticsQuery->reorder(); // Remove ordering for aggregation

        $aggregates = $analyticsQuery->selectRaw('status, count(*) as count, sum(grand_total) as value')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $stats = [
            'total_count' => 0,
            'total_value' => 0,
        ];

        // Initialize all statuses with 0
        $allStatuses = [
            Estimate::STATUS_DRAFT,
            Estimate::STATUS_WAITING_APPROVAL,
            Estimate::STATUS_APPROVED,
            Estimate::STATUS_SENT,
            Estimate::STATUS_ACCEPTED,
            Estimate::STATUS_DECLINED, // and Expired if exists
        ];

        foreach ($allStatuses as $status) {
            $row = $aggregates->get($status);
            $count = $row ? $row->count : 0;
            $value = $row ? $row->value : 0;

            $stats[$status . '_count'] = $count;
            $stats[$status . '_value'] = $value;

            $stats['total_count'] += $count;
            $stats['total_value'] += $value;
        }

        // Conversion Rate
        $stats['conversion_rate'] = $stats['total_count'] > 0
            ? round(($stats['accepted_count'] / $stats['total_count']) * 100, 1)
            : 0;

        $estimates = $query->paginate(15)->withQueryString();

        // Status Counts for Tabs (Global context usually better for tabs to show what's available)
        // But if we filter by Client, maybe tabs should update? Let's keep tabs global for simplicity or
        // strictly based on other filters? Standard is usually global counts on tabs unless filtered.
        // Let's keep simple global counts for the specific user scope.
        $scopeQuery = Estimate::current();
        if (!auth()->user()->hasRole(['super_admin', 'admin', 'estimator_admin'])) {
            $scopeQuery->where(function ($q) {
                $q->where('created_by', auth()->id())
                    ->orWhereHas('manualFollowers', function ($f) {
                        $f->where('user_id', auth()->id());
                    })
                    ->orWhereHas('approvals', function ($a) {
                        $a->where('user_id', auth()->id());
                    });
            });
        }

        $counts = [
            'all' => (clone $scopeQuery)->count(),
            'draft' => (clone $scopeQuery)->where('status', Estimate::STATUS_DRAFT)->count(),
            'waiting_approval' => (clone $scopeQuery)->where('status', Estimate::STATUS_WAITING_APPROVAL)->count(),
            'approved' => (clone $scopeQuery)->where('status', Estimate::STATUS_APPROVED)->count(),
            'sent' => (clone $scopeQuery)->where('status', Estimate::STATUS_SENT)->count(),
            'accepted' => (clone $scopeQuery)->where('status', Estimate::STATUS_ACCEPTED)->count(),
            'declined' => (clone $scopeQuery)->where('status', Estimate::STATUS_DECLINED)->count(),
        ];

        $clients = Client::orderBy('name')->get(); // For filter dropdown

        return view('estimates.index', compact('estimates', 'counts', 'stats', 'clients'));
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'estimate_ids' => 'required|array',
            'estimate_ids.*' => 'exists:estimates,id',
            'action' => 'required|in:delete,mark_sent,mark_accepted,mark_declined,mark_draft',
        ]);

        $count = 0;
        $estimates = Estimate::whereIn('id', $request->estimate_ids)->get();

        foreach ($estimates as $estimate) {
            // Authorize
            if ($request->action === 'delete') {
                if (auth()->user()->can('delete', $estimate)) {
                    $estimate->delete();
                    $count++;
                }
            } else {
                if (auth()->user()->can('update', $estimate)) {
                    $statusMap = [
                        'mark_sent' => Estimate::STATUS_SENT,
                        'mark_accepted' => Estimate::STATUS_ACCEPTED,
                        'mark_declined' => Estimate::STATUS_DECLINED,
                        'mark_draft' => Estimate::STATUS_DRAFT,
                    ];

                    if (isset($statusMap[$request->action])) {
                        $estimate->update(['status' => $statusMap[$request->action]]);
                        ActivityLog::log('status_updated', $estimate, "Bulk action: status changed to " . $statusMap[$request->action] . " by " . auth()->user()->name);

                        // Dispatch Event based on action
                        switch ($request->action) {
                            case 'mark_sent':
                                $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateSent($estimate, auth()->id(), 'bulk_action'));
                                break;
                            case 'mark_accepted':
                                $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateApproved($estimate, auth()->id(), 'manual_bulk'));
                                break;
                            case 'mark_declined':
                                $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateDeclined($estimate, auth()->id(), 'manual_bulk'));
                                break;
                        }

                        $count++;
                    }
                }
            }
        }

        return back()->with('success', "Processed {$count} estimates successfully.");
    }

    /**
     * Show the form for creating a new estimate.
     */
    public function create()
    {
        $this->authorize('create', Estimate::class);

        $products = Product::with(['images', 'options.values'])->get();
        $templates = $this->hydrateTemplates(RoomTemplate::all());

        $packages = ItemPackage::all()->values();
        $clients = Client::orderBy('name')->get();
        $approvalChains = ApprovalChain::activeWithSteps();
        $pdfTemplates = PdfTemplate::where('is_active', true)->get();
        $unitTypes = UnitType::all();
        $categories = ProductCategory::orderBy('name')->get();

        $settings = Setting::getAllCached();

        $defaults = [
            'currency' => $settings['currency_code'] ?? 'USD',
            'tax_1_name' => $settings['tax_1_name'] ?? 'Tax 1',
            'tax_1_rate' => (float) ($settings['tax_1_rate'] ?? 0),
            'tax_2_name' => $settings['tax_2_name'] ?? 'Tax 2',
            'tax_2_rate' => (float) ($settings['tax_2_rate'] ?? 0),
            'terms' => '',
            'client_note' => $settings['estimate_client_note'] ?? '',
        ];

        return view('estimates.create', compact('products', 'templates', 'packages', 'defaults', 'clients', 'approvalChains', 'pdfTemplates', 'unitTypes', 'categories'));
    }

    /**
     * Store a newly created estimate in storage.
     */
    public function store(\App\Http\Requests\StoreEstimateRequest $request)
    {
        // Validation handled by FormRequest
        $validated = $request->validated();

        try {
            $itemsOrSections = $request->type === 'room_based'
                ? ($validated['sections'] ?? [])
                : ($validated['items'] ?? []);

            // Ensure we don't pass the raw items/sections array as part of the main estimate attributes
            // createEstimate expects: (data, itemsOrSections, type)
            // We should strip items/sections from $validated for the first arg.
            $estimateData = \Illuminate\Support\Arr::except($validated, ['items', 'sections']);

            $estimate = $this->estimateService->createEstimate($estimateData, $itemsOrSections, $request->type);

            return redirect()->route('estimates.show', $estimate)->with('success', 'Estimate created successfully.');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Estimate Creation Failed', [
                'user_id' => auth()->id(),
                'input' => \Illuminate\Support\Arr::except($validated, ['sections', 'items']), // Log non-heavy input
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withInput()->withErrors(['error' => 'Failed to create estimate. System error logged.']);
        }
    }

    /**
     * Display the specified estimate.
     */
    public function show(Estimate $estimate)
    {
        $this->authorize('view', $estimate);

        $estimate->load('items.product.images', 'items.unitType', 'items.comments.user', 'sections.items.product.images', 'sections.items.unitType', 'sections.items.comments.user', 'approvals.user', 'checklistItems', 'creator');

        // Explicitly make cost, internal_note, and admin_note visible for staff/admin view
        $estimate->makeVisible(['admin_note']);
        if ($estimate->items) {
            $estimate->items->makeVisible(['cost', 'internal_note']);
        }
        if ($estimate->sections) {
            foreach ($estimate->sections as $section) {
                if ($section->items) {
                    $section->items->makeVisible(['cost', 'internal_note']);
                }
            }
        }
        $checklists = ApprovalChecklist::getAllCached();
        $declineReasons = DeclineReason::getActiveCached();

        // fetch version history
        $root = $estimate->parent ?? $estimate;
        $allVersions = Estimate::where('id', $root->id)->orWhere('parent_id', $root->id)->orderBy('version', 'desc')->get();

        // Check for current user's pending approval
        $userApproval = $estimate->approvals()
            ->where('user_id', auth()->id())
            ->where('status', 'pending')
            ->first();

        // Calculate Diff if previous version exists
        $diff = null;
        if ($estimate->version > 1) {
            $previousVersion = $allVersions->firstWhere('version', $estimate->version - 1);
            if ($previousVersion) {
                $diff = (new \App\Services\EstimateDiffService)->calculateDiff($previousVersion, $estimate);
            }
        }

        // Fetch Activity Logs for ALL versions
        $familyIds = $allVersions->pluck('id')->toArray();
        $activityLogs = ActivityLog::where('subject_type', Estimate::class)
            ->whereIn('subject_id', $familyIds)
            ->with('user')
            ->latest()
            ->latest()
            ->get();

        $latestVersion = Estimate::where('parent_id', $estimate->parent_id ?? $estimate->id)
            ->orWhere('id', $estimate->parent_id ?? $estimate->id)
            ->orderBy('version', 'desc')
            ->first();

        $potentialFollowers = User::where('id', '!=', $estimate->created_by)
            ->orderBy('name')
            ->get();

        return view('estimates.show', compact('estimate', 'checklists', 'declineReasons', 'allVersions', 'userApproval', 'diff', 'activityLogs', 'latestVersion', 'potentialFollowers'));
    }

    /**
     * Show the form for editing the specified estimate.
     */
    public function edit(Estimate $estimate)
    {
        $this->authorize('update', $estimate);

        $products = Product::with(['images', 'options.values'])->get();
        $templates = $this->hydrateTemplates(RoomTemplate::all());
        $packages = ItemPackage::all()->values();
        $clients = Client::orderBy('name')->get();
        $approvalChains = ApprovalChain::activeWithSteps();
        $pdfTemplates = PdfTemplate::where('is_active', true)->get();
        $unitTypes = UnitType::all();
        $categories = ProductCategory::orderBy('name')->get();
        $estimate->load(['sections.items.product.images', 'items.product.images', 'couponCode']);

        // Explicitly make cost, internal_note, and admin_note visible for staff/admin view
        $estimate->makeVisible(['admin_note']);
        if ($estimate->items) {
            $estimate->items->makeVisible(['cost', 'internal_note']);
        }
        if ($estimate->sections) {
            foreach ($estimate->sections as $section) {
                if ($section->items) {
                    $section->items->makeVisible(['cost', 'internal_note']);
                }
            }
        }

        $settings = Setting::getAllCached();
        $defaults = [
            'currency' => $settings['currency_code'] ?? 'USD',
            'tax_1_name' => $settings['tax_1_name'] ?? 'Tax 1',
            'tax_1_rate' => (float) ($settings['tax_1_rate'] ?? 0),
            'tax_2_name' => $settings['tax_2_name'] ?? 'Tax 2',
            'tax_2_rate' => (float) ($settings['tax_2_rate'] ?? 0),
            'terms' => '',
            'client_note' => $settings['estimate_client_note'] ?? '',
        ];

        return view('estimates.edit', compact('estimate', 'products', 'templates', 'packages', 'clients', 'approvalChains', 'pdfTemplates', 'defaults', 'unitTypes', 'categories'));
    }

    /**
     * Update the specified estimate in storage.
     */
    /**
     * Update the specified estimate in storage.
     */
    public function update(\App\Http\Requests\UpdateEstimateRequest $request, Estimate $estimate)
    {
        // Concurrency Check (Logic remains in Controller or could be in FormRequest, 
        // but Request already validated last_update_timestamp format. 
        // Actual logic:
        if ($request->has('last_update_timestamp')) {
            $clientTimestamp = \Carbon\Carbon::parse($request->last_update_timestamp);
            if ($estimate->updated_at->gt($clientTimestamp)) {
                return back()->withInput()->withErrors(['error' => 'This estimate has been modified by another user. Please refresh.']);
            }
        }

        $validated = $request->validated();

        try {
            $itemsOrSections = $request->type === 'room_based'
                ? ($validated['sections'] ?? [])
                : ($validated['items'] ?? []);

            $estimateData = \Illuminate\Support\Arr::except($validated, ['items', 'sections', 'last_update_timestamp']);

            // Service handles branching, transactions, logic
            $updatedEstimate = $this->estimateService->updateEstimate($estimate, $estimateData, $itemsOrSections, $request->type);

            $msg = 'Estimate updated successfully.';
            if ($updatedEstimate->id !== $estimate->id) {
                $msg = "A new version ({$updatedEstimate->estimate_number}) was created because the original was locked or shared.";
            }

            return redirect()->route('estimates.show', $updatedEstimate)->with('success', $msg);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Estimate Update Failed', [
                'estimate_id' => $estimate->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withInput()->withErrors(['error' => 'Failed to update estimate. System error logged.']);
        }
    }

    /**
     * Create a new version of the estimate.
     */
    public function createVersion(Estimate $estimate)
    {
        $newEstimate = $this->estimateService->createVersion($estimate);

        return redirect()->route('estimates.edit', $newEstimate)
            ->with('success', 'New version created! You are now editing version ' . $newEstimate->version);
    }

    /**
     * Copy an existing estimate to a new one.
     */
    public function copy(Estimate $estimate)
    {
        $this->authorize('view', $estimate);

        $newEstimate = $this->estimateService->copy($estimate);

        return redirect()->route('estimates.edit', $newEstimate)->with('success', 'Estimate copied successfully.');
    }

    /**
     * Manually update the status of an estimate.
     */
    public function markAs(Request $request, Estimate $estimate, $status)
    {
        $this->authorize('update', $estimate);

        return DB::transaction(function () use ($estimate, $status) {
            // Lock the record
            $estimate = Estimate::where('id', $estimate->id)->lockForUpdate()->firstOrFail();

            if (!$estimate->canTransitionTo($status)) {
                return back()->with('error', "Cannot transition from {$estimate->status} to {$status}.");
            }

            // GUARD: Enforce Approval Chain for SENT status
            if ($status === Estimate::STATUS_SENT && !auth()->user()->hasRole(['super_admin', 'admin'])) {
                if ($estimate->approvalChain && !$estimate->isFullyApproved()) {
                    return back()->with('error', "Cannot mark as SENT: This estimate has not completed its approval chain.");
                }
            }

            $oldStatus = $estimate->status;
            $estimate->update(['status' => $status]);

            ActivityLog::log('status_updated', $estimate, "Estimate #{$estimate->estimate_number} status manually changed from {$oldStatus} to {$status}.");

            // Dispatch specific status events
            switch ($status) {
                case Estimate::STATUS_SENT:
                    $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateSent($estimate, auth()->id(), 'manual_mark'));
                    break;
                case Estimate::STATUS_ACCEPTED:
                    $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateApproved($estimate, auth()->id(), 'manual_mark'));
                    break;
                case Estimate::STATUS_DECLINED:
                    $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateDeclined($estimate, auth()->id(), 'manual_mark'));
                    break;
                case Estimate::STATUS_EXPIRED:
                    $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateExpired($estimate));
                    break;
            }

            return back()->with('success', 'Estimate marked as ' . ucfirst($status) . '.');
        });
    }

    /**
     * Revert the estimate to draft status.
     */
    public function revertToDraft(Estimate $estimate)
    {
        $this->authorize('revertToDraft', $estimate);

        return DB::transaction(function () use ($estimate) {
            $estimate = Estimate::where('id', $estimate->id)->lockForUpdate()->firstOrFail();

            $estimate->update(['status' => Estimate::STATUS_DRAFT]);

            ActivityLog::log('status_updated', $estimate, "Estimate #{$estimate->estimate_number} reverted to draft.");

            return back()->with('success', 'Estimate reverted to draft.');
        });
    }

    /**
     * Send estimate to client via email.
     */
    public function sendToClient(Estimate $estimate)
    {
        $this->authorize('update', $estimate);

        // GUARD: Enforce Approval Chain before Sending
        if (!auth()->user()->hasRole(['super_admin', 'admin'])) {
            if ($estimate->approvalChain && !$estimate->isFullyApproved()) {
                return back()->with('error', 'Cannot send to client: Estimate approval chain is incomplete.');
            }
        }

        try {
            $this->estimateService->sendToClient($estimate);

            return back()->with('success', 'Estimate sent to client successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error sending email: ' . $e->getMessage());
        }
    }

    /**
     * Batch download estimates as a ZIP of PDFs.
     */
    public function batchDownload(Request $request)
    {
        $request->validate([
            'estimate_ids' => 'required|array',
            'estimate_ids.*' => 'exists:estimates,id',
        ]);

        // Dispatch Job
        \App\Jobs\GenerateBatchPdfZip::dispatch($request->estimate_ids, auth()->id());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => 'Batch export started.']);
        }

        return back()->with('success', 'Batch export started. You will receive an email/notification when it is ready.');
    }

    /**
     * Download the estimate as a PDF.
     */
    public function downloadPdf(Estimate $estimate)
    {
        $this->authorize('view', $estimate);

        $template = $estimate->pdfTemplate ?? PdfTemplate::where('is_active', true)->where('is_default', true)->first();

        if (!$template) {
            return back()->with('error', 'No active PDF template found.');
        }

        $service = new PdfRenderingService;
        // Increase memory limit for PDF generation
        ini_set('memory_limit', '512M');
        $path = $service->renderAndCache($template, $estimate);

        if (!$path || !file_exists($path)) {
            return back()->with('error', 'Failed to generate PDF.');
        }

        // Log analytic
        app(AnalyticsService::class)->logAccess($estimate, 'download');

        return response()->download($path, "Estimate-{$estimate->estimate_number}.pdf");
    }

    /**
     * Preview the estimate.
     */
    public function preview(Request $request)
    {
        return response()->json(['success' => true]);
    }

    /**
     * Preview the estimate as a client (Admin View).
     */
    public function portalPreview(Estimate $estimate)
    {
        $this->authorize('view', $estimate);

        // Load items for the view
        $estimate->load(['sections.items', 'comments.user']);

        // Render PDF Template HTML
        $template = $estimate->pdfTemplate ?? \App\Models\PdfTemplate::where('is_active', true)->where('is_default', true)->first() ?? \App\Models\PdfTemplate::first();

        $htmlContent = '';
        if ($template) {
            $service = new PdfRenderingService;
            $htmlContent = $service->render($template, $estimate, true);
        }

        return view('portal.estimates.show', compact('estimate', 'htmlContent'));
    }

    /**
     * Print the estimate.
     */
    public function print(Estimate $estimate)
    {
        $this->authorize('view', $estimate);

        // Load default template if none specific
        $template = $estimate->pdfTemplate ?? PdfTemplate::where('is_active', true)->where('is_default', true)->first();

        // Fallback to simpler view or errors if NO template exists (e.g. fresh DB)
        if (!$template) {
            $template = PdfTemplate::first(); // Grab ANY template
        }

        if (!$template) {
            abort(404, 'No PDF Templates found in system.');
        }

        $service = new PdfRenderingService;
        $html = $service->render($template, $estimate);

        // Return inline HTML
        return response($html);
    }

    /**
     * Duplicate an estimate item.
     */
    public function duplicateItem(EstimateItem $item)
    {
        $this->authorize('update', $item->estimate);

        $newItem = $item->replicate();
        $newItem->order_index = $item->order_index + 1;
        $newItem->save();

        return back()->with('success', 'Item duplicated.');
    }
    /**
     * Store an internal reply/comment.
     */
    public function storeComment(Request $request, Estimate $estimate)
    {
        $this->authorize('update', $estimate); // assuming permission

        $validated = $request->validate([
            'comment' => 'required|string|max:5000',
        ]);

        $comment = $estimate->comments()->create([
            'commentable_type' => Estimate::class,
            'commentable_id' => $estimate->id,
            'user_id' => auth()->id(),
            'comment' => $validated['comment'],
            'type' => 'internal', // Staff comments are internal (but communicated to client via thread view?)
            // Wait, if it's a thread shared with client, type should be 'internal'?
            // The portal displays all comments or just client/client?
            // Re-checking portal logic: specific scope?
            // Portal controller shows: all comments?
        ]);

        // Check Portal Controller show method logic?
        // Actually, we should probably set type='client' or similar if we want client to see it?
        // But the schema has 'type' enum ['client', 'internal'].
        // Internal usually means "internal team only". 
        // If this is a conversation with the client, we might need a status change or specific type.
        // However, for now, let's treat staff replies as visible if we update the portal to show them.
        // Assuming 'internal' might be filtered out in portal.
        // Let's check EstimateComment model scopes.

        // Notify Creator and Followers (excluding self)
        $followers = $estimate->followers->reject(fn($u) => $u->id === auth()->id());
        foreach ($followers as $follower) {
            $follower->notify(new \App\Notifications\EstimateCommentNotification($comment, $estimate));
        }

        return back()->with('success', 'Comment posted.');
    }
    /**
     * Remove the specified estimate from storage.
     */
    public function destroy(Estimate $estimate)
    {
        $this->authorize('delete', $estimate);

        return DB::transaction(function () use ($estimate) {
            $estimateNumber = $estimate->estimate_number;
            $isCurrentVersion = $estimate->is_current_version;
            $parentId = $estimate->parent_id;

            // Soft delete the estimate (cascade only applies to hard deletes, so parent is safe)
            $estimate->delete();

            // If we deleted the current version and it has a parent, restore the previous version as current
            if ($isCurrentVersion && $parentId) {
                $previousVersion = Estimate::where('id', $parentId)
                    ->orWhere('parent_id', $parentId)
                    ->where('id', '!=', $estimate->id)
                    ->orderBy('version', 'desc')
                    ->first();

                if ($previousVersion) {
                    $previousVersion->update(['is_current_version' => true]);
                }
            }

            ActivityLog::log('estimate_deleted', $estimate, "Estimate #{$estimateNumber} deleted by " . auth()->user()->name);

            return redirect()->route('estimates.index')->with('success', 'Estimate deleted successfully.');
        });
    }
    public function approveVersion(Estimate $estimate)
    {
        $this->authorize('update', $estimate);

        // Strictly allow only Creator or Admins to approve (not followers with edit rights)
        if (auth()->id() !== $estimate->created_by && !auth()->user()->hasRole(['super_admin', 'admin'])) {
            abort(403, 'Only the creator or an admin can approve changes.');
        }

        return DB::transaction(function () use ($estimate) {
            // Find the root parent
            $parentId = $estimate->parent_id ?? $estimate->id;

            // Lock the entire family family to ensure version consistency
            Estimate::where('id', $parentId)->orWhere('parent_id', $parentId)->lockForUpdate()->get();

            // Refetch current estimate within lock
            $estimate = Estimate::find($estimate->id);

            // 1. Mark this version as current
            $estimate->update(['is_current_version' => true]);

            // 3. Mark ALL others in this family as NOT current
            // (This includes the root parent and all sibling versions)
            Estimate::where(function ($q) use ($parentId) {
                $q->where('id', $parentId)
                    ->orWhere('parent_id', $parentId);
            })
                ->where('id', '!=', $estimate->id)
                ->update(['is_current_version' => false]);

            ActivityLog::log('version_approved', $estimate, "Version #{$estimate->version} approved by " . auth()->user()->name);

            return back()->with('success', 'Version approved and set as live.');
        });
    }

    public function addFollower(Request $request, Estimate $estimate)
    {
        $this->authorize('update', $estimate);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'permissions' => 'nullable|array',
        ]);

        // Check duplicate
        if ($estimate->manualFollowers()->where('user_id', $validated['user_id'])->exists()) {
            return back()->with('error', 'User is already a follower.');
        }

        $estimate->manualFollowers()->create([
            'user_id' => $validated['user_id'],
            'permissions' => $validated['permissions'] ?? [],
        ]);

        $user = \App\Models\User::find($validated['user_id']);
        if ($user) {
            $user->notify(new \App\Notifications\EstimateFollowerAdded($estimate, $validated['permissions'] ?? []));
        }

        return back()->with('success', 'Follower added.');
    }

    public function removeFollower(Estimate $estimate, \App\Models\User $user)
    {
        $this->authorize('update', $estimate);

        $estimate->manualFollowers()->where('user_id', $user->id)->delete();

        return back()->with('success', 'Follower removed.');
    }

    private function hydrateTemplates($templates)
    {
        // Hydrate Template Items with Product Data
        $productIds = [];
        $templates->each(function ($t) use (&$productIds) {
            if (!empty($t->items) && is_array($t->items)) {
                foreach ($t->items as $item) {
                    if (isset($item['product_id']))
                        $productIds[] = $item['product_id'];
                }
            }
        });

        $templateProducts = Product::whereIn('id', array_unique($productIds))->with('images')->get()->keyBy('id');

        return $templates->transform(function ($t) use ($templateProducts) {
            $items = $t->items ?? [];
            if (is_array($items)) {
                foreach ($items as &$item) {
                    if (isset($item['product_id']) && isset($templateProducts[$item['product_id']])) {
                        $item['product'] = $templateProducts[$item['product_id']];
                    }
                }
                $t->items = $items;
            }
            return $t;
        })->values();
    }
}
