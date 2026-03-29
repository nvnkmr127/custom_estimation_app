<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ApprovalChain;
use App\Models\ChangeRequestChecklist;
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

        $aggregates = $analyticsQuery->selectRaw('estimate_status, count(*) as count, sum(grand_total) as value')
            ->groupBy('estimate_status')
            ->get()
            ->keyBy('estimate_status');

        $stats = [
            'total_count' => 0,
            'total_value' => 0,
        ];

        // Initialize all statuses with 0
        $allStatuses = [
            Estimate::EST_STATUS_DRAFT,
            Estimate::EST_STATUS_PENDING_APPROVAL,
            Estimate::EST_STATUS_APPROVED,
            Estimate::EST_STATUS_SENT,
            Estimate::EST_STATUS_ACCEPTED,
            Estimate::EST_STATUS_DECLINED, // and Expired if exists
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
            'draft' => (clone $scopeQuery)->where('estimate_status', Estimate::EST_STATUS_DRAFT)->count(),
            'waiting_approval' => (clone $scopeQuery)->where('estimate_status', Estimate::EST_STATUS_PENDING_APPROVAL)->count(),
            'approved' => (clone $scopeQuery)->where('estimate_status', Estimate::EST_STATUS_APPROVED)->count(),
            'sent' => (clone $scopeQuery)->where('estimate_status', Estimate::EST_STATUS_SENT)->count(),
            'accepted' => (clone $scopeQuery)->where('estimate_status', Estimate::EST_STATUS_ACCEPTED)->count(),
            'declined' => (clone $scopeQuery)->where('estimate_status', Estimate::EST_STATUS_DECLINED)->count(),
        ];

        $clients = Client::orderBy('name')->get(); // For filter dropdown

        return view('estimates.index', compact('estimates', 'counts', 'stats', 'clients'));
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'estimate_ids' => 'required|array',
            'estimate_ids.*' => 'exists:estimates,id',
            'action' => 'required|in:delete,mark_sent,mark_declined,mark_draft',
        ]);

        $count = 0;
        $estimates = Estimate::whereIn('id', $request->estimate_ids)->get();

        foreach ($estimates as $estimate) {
            /** @var Estimate $estimate */

            // Authorize
            if ($request->action === 'delete') {
                if (auth()->user()->can('delete', $estimate)) {
                    $estimate->delete();
                    $count++;
                }
            } else {
                if (auth()->user()->can('update', $estimate)) {
                    $stateService = app(\App\Services\Estimates\EstimateStateService::class);

                    try {
                        switch ($request->action) {
                            case 'mark_sent':
                                $stateService->transitionClientStatus($estimate, Estimate::CLT_STATUS_SENT, true);
                                $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateSent($estimate, auth()->id(), 'bulk_action'));
                                break;
                            case 'mark_declined':
                                $stateService->transitionClientStatus($estimate, Estimate::CLT_STATUS_DECLINED, true);
                                $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateDeclined($estimate, auth()->id(), 'manual_bulk'));
                                break;
                            case 'mark_draft':
                                $stateService->transitionEstimateStatus($estimate, Estimate::EST_STATUS_DRAFT);
                                break;
                        }

                        ActivityLog::log('status_updated', $estimate, "Bulk action: status changed by " . auth()->user()->name);
                        $count++;
                    } catch (\Exception $e) {
                        \Log::error("Failed to bulk update estimate {$estimate->id}: " . $e->getMessage());
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

        $products = Product::with(['images', 'options.values'])->active()->get();
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
            'currency_symbol' => Setting::getCurrencySymbol(),
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
            $sections = $validated['sections'] ?? [];
            $items = $validated['items'] ?? [];
            $estimateData = \Illuminate\Support\Arr::except($validated, ['items', 'sections']);

            $estimate = $this->estimateService->createEstimate($estimateData, $sections, $items, $request->type);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Estimate created successfully.',
                    'estimate_id' => $estimate->id,
                    'estimate_number' => $estimate->estimate_number,
                    'redirect_url' => route('estimates.edit', $estimate),
                    'last_update_timestamp' => $estimate->updated_at->toDateTimeString()
                ]);
            }

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

        $products = Product::with(['images', 'options.values'])->active()->get();
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
            $sections = $validated['sections'] ?? [];
            $items = $validated['items'] ?? [];
            $deletedSections = $request->input('deleted_sections', []);
            $deletedItems = $request->input('deleted_items', []);
            $estimateData = \Illuminate\Support\Arr::except($validated, ['items', 'sections', 'last_update_timestamp']);

            // Service handles branching, transactions, logic
            $updatedEstimate = $this->estimateService->updateEstimate($estimate, $estimateData, $sections, $items, $request->type, false, $deletedSections, $deletedItems);

            $msg = 'Estimate updated successfully.';
            $isBranched = $updatedEstimate->id !== $estimate->id;

            if ($isBranched) {
                $msg = "A new version ({$updatedEstimate->estimate_number}) was created because the original was locked or shared.";
            }

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'estimate_id' => $updatedEstimate->id,
                    'estimate_number' => $updatedEstimate->estimate_number,
                    'is_branched' => $isBranched,
                    'redirect_url' => route('estimates.edit', $updatedEstimate),
                    'last_update_timestamp' => $updatedEstimate->updated_at->toDateTimeString()
                ]);
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

        return redirect()->route('estimates.edit', ['estimate' => $newEstimate->id, 'duplicate' => 1])->with('success', 'Estimate copied successfully.');
    }

    /**
     * Manually update the status of an estimate.
     */
    public function markAs(Request $request, Estimate $estimate, $status)
    {
        $this->authorize('update', $estimate);

        try {
            return DB::transaction(function () use ($estimate, $status) {
                // Lock the record
                $estimate = Estimate::where('id', $estimate->id)->lockForUpdate()->firstOrFail();

                if (!$estimate->canTransitionTo($status)) {
                    throw new \InvalidArgumentException("Cannot transition from {$estimate->estimate_status} to {$status}.");
                }

                $oldStatus = $estimate->estimate_status;

                $stateService = app(\App\Services\Estimates\EstimateStateService::class);

                // Use State Service for Transition
                if (in_array($status, [Estimate::EST_STATUS_SENT, Estimate::EST_STATUS_DECLINED, Estimate::EST_STATUS_EXPIRED])) {
                    // These are client lifecycle related
                    $stateService->transitionClientStatus($estimate, $status, true);
                } else {
                    // Internal transitions
                    $stateService->transitionEstimateStatus($estimate, $status);
                }

                ActivityLog::log('status_updated', $estimate, "Estimate #{$estimate->estimate_number} status manually changed from {$oldStatus} to {$status}.");

                // Dispatch specific status events
                switch ($status) {
                    case Estimate::EST_STATUS_SENT:
                        $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateSent($estimate, auth()->id(), 'manual_mark'));
                        break;
                    case Estimate::EST_STATUS_DECLINED:
                        $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateDeclined($estimate, auth()->id(), 'manual_mark'));
                        break;
                    case Estimate::EST_STATUS_EXPIRED:
                        $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateExpired($estimate));
                        break;
                }

                return back()->with('success', 'Estimate marked as ' . ucfirst($status) . '.');
            });
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', "Failed to update estimate status: " . $e->getMessage());
        }
    }

    /**
     * Extend the expiry of an estimate.
     */
    public function extendExpiry(Request $request, Estimate $estimate, \App\Services\Estimates\EstimateStateService $stateService)
    {
        $this->authorize('extendExpiry', $estimate);

        $request->validate([
            'expiry_date' => 'required|date|after:today',
        ]);

        try {
            $stateService->extendExpiry($estimate, \Carbon\Carbon::parse($request->expiry_date));
            return back()->with('success', 'Estimate expiry extended successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to extend expiry: ' . $e->getMessage());
        }
    }

    /**
     * Revert the estimate to draft status.
     */
    public function revertToDraft(Estimate $estimate)
    {
        $this->authorize('revertToDraft', $estimate);

        return DB::transaction(function () use ($estimate) {
            $estimate = Estimate::where('id', $estimate->id)->lockForUpdate()->firstOrFail();

            $estimate->estimate_status = Estimate::EST_STATUS_DRAFT;
            $estimate->save();

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

        try {
            $this->estimateService->sendToClient($estimate);
            return back()->with('success', 'Estimate sent to client successfully.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
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
        // Increase memory limit and execution time for PDF generation
        ini_set('memory_limit', '512M');
        set_time_limit(300);
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
     * Calculate estimate totals via AJAX.
     */
    public function calculate(Request $request)
    {
        $data = $request->all();

        // Create a temporary estimate instance
        $estimate = new Estimate($data);

        // Populate items
        $items = collect([]);
        if ($request->type === 'room_based') {
            foreach ($request->sections ?? [] as $sectionData) {
                foreach ($sectionData['items'] ?? [] as $itemData) {
                    $items->push(new EstimateItem($itemData));
                }
            }
        } else {
            foreach ($request->items ?? [] as $itemData) {
                $items->push(new EstimateItem($itemData));
            }
        }

        $estimate->setRelation('items', $items);

        // Use the PriceCalculator
        $calculator = new \App\Services\Calculations\PriceCalculator();
        $results = $calculator->calculate($estimate);

        return response()->json([
            'subtotal' => $results['estimate_updates']['subtotal'],
            'total_tax' => $results['estimate_updates']['total_tax'],
            'discount' => $results['estimate_updates']['discount_total'],
            'grand_total' => $results['estimate_updates']['grand_total'],
            'approval_chain_id' => $results['estimate_updates']['approval_chain_id'],
        ]);
    }

    /**
     * Preview the estimate as a client (Admin View).
     */
    public function portalPreview(Estimate $estimate)
    {
        $this->authorize('view', $estimate);

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
            $service = new PdfRenderingService;
            $htmlContent = $service->render($template, $estimate, true);
        }

        // Prepare unified comments logic
        $commentData = collect();

        $transform = function ($c, $itemName = null, $parent = null) {
            return [
                'id' => $c->id,
                'comment' => $c->comment,
                'type' => $c->type,
                'created_at' => $c->created_at,
                // Inherit commentable info from parent if missing (crucial for replies)
                'commentable_type' => $c->commentable_type ?? ($parent ? $parent->commentable_type : null),
                'commentable_id' => $c->commentable_id ?? ($parent ? $parent->commentable_id : null),
                'user' => $c->user,
                'item_name' => $itemName ?? (($c->commentable_type === 'App\Models\EstimateItem' && $c->commentable) ? $c->commentable->name : null)
            ];
        };

        // 1. General Estimate Comments & Replies
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


        // 2. Item Comments & Replies
        foreach ($estimate->sections as $section) {
            foreach ($section->items as $item) {
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

        $comments = $commentData->filter()->unique('id')->sortBy('created_at')->values();
        $checklists = ChangeRequestChecklist::getAllCached();

        return view('portal.estimates.show', compact('estimate', 'htmlContent', 'comments', 'checklists'));
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
        \Log::info("EstimateController: storeComment called", [
            'estimate_id' => $estimate->id,
            'request_all' => $request->all()
        ]);

        $this->authorize('update', $estimate);

        $validated = $request->validate([
            'comment' => 'required|string|max:5000',
            'commentable_type' => 'nullable|string',
            'commentable_id' => 'nullable|numeric',
        ]);

        $type = Estimate::class;
        $id = $estimate->id;

        // If specific item is targeted
        if (!empty($validated['commentable_type']) && !empty($validated['commentable_id'])) {
            // Allow commenting on Items
            if ($validated['commentable_type'] === 'App\\Models\\EstimateItem') {
                // Verify item belongs to estimate for security
                $item = \App\Models\EstimateItem::where('id', $validated['commentable_id'])
                    ->where('estimate_id', $estimate->id)
                    ->first();
                if ($item) {
                    $type = 'App\\Models\\EstimateItem';
                    $id = $item->id;
                }
            }
        }

        \Log::info("EstimateController: Creating comment", [
            'type' => $type,
            'id' => $id,
            'user_id' => auth()->id()
        ]);

        $comment = $estimate->comments()->create([
            'commentable_type' => $type,
            'commentable_id' => $id,
            'user_id' => auth()->id(),
            'comment' => $validated['comment'],
            'type' => 'internal',
        ]);

        \Log::info("Admin Posted Comment: ID {$comment->id} on {$comment->commentable_type} #{$comment->commentable_id}");

        // Notify Creator and Followers (excluding self)
        $followers = $estimate->followers->reject(fn($u) => $u->id === auth()->id());
        foreach ($followers as $follower) {
            $follower->notify(new \App\Notifications\EstimateCommentNotification($comment, $estimate));
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'comment' => $comment]);
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

    /**
     * Fetch the history of deleted sections/items for this estimate.
     */
    public function restoreHistory(Estimate $estimate)
    {
        $id = $estimate->id;
        
        $itemTimestamps = \App\Models\EstimateItem::onlyTrashed()
            ->where('estimate_id', $id)
            ->pluck('deleted_at')
            ->map(fn($t) => $t ? $t->toDateTimeString() : null)
            ->filter()
            ->unique();

        $sectionTimestamps = \App\Models\EstimateSection::onlyTrashed()
            ->where('estimate_id', $id)
            ->pluck('deleted_at')
            ->map(fn($t) => $t ? $t->toDateTimeString() : null)
            ->filter()
            ->unique();
        
        $allTimestamps = $itemTimestamps->merge($sectionTimestamps)
            ->unique()
            ->values()
            ->sortDesc()
            ->values();

        $sessions = $allTimestamps->map(function($timestamp, $index) use ($id) {
            $sections = \App\Models\EstimateSection::onlyTrashed()
                ->where('estimate_id', $id)
                ->where('deleted_at', $timestamp)
                ->get(['name']);
            
            $items = \App\Models\EstimateItem::onlyTrashed()
                ->where('estimate_id', $id)
                ->where('deleted_at', $timestamp)
                ->get(['name']);

            return [
                'timestamp' => $timestamp,
                'time_format' => \Carbon\Carbon::parse($timestamp)->diffForHumans() . " (" . \Carbon\Carbon::parse($timestamp)->format('M d, H:i') . ")",
                'rooms' => $sections->pluck('name')->unique()->values(),
                'room_count' => $sections->count(),
                'item_preview' => $items->take(5)->pluck('name')->implode(', '),
                'item_count' => $items->count(),
                'label' => ($index === 0) ? "Most Recent" : (($index === 1) ? "Previous Version" : ""),
            ];
        });

        return response()->json($sessions);
    }

    /**
     * Restore data from a specific deletion session via UI.
     */
    public function restoreSession(\Illuminate\Http\Request $request, Estimate $estimate)
    {
        $timestamp = $request->input('timestamp');
        if (!$timestamp) return response()->json(['error' => 'Missing timestamp'], 400);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $restoredSections = \App\Models\EstimateSection::onlyTrashed()
                ->where('estimate_id', $estimate->id)
                ->where('deleted_at', $timestamp)
                ->restore();

            $restoredItems = \App\Models\EstimateItem::onlyTrashed()
                ->where('estimate_id', $estimate->id)
                ->where('deleted_at', $timestamp)
                ->restore();

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Restored {$restoredSections} rooms and {$restoredItems} items."
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
