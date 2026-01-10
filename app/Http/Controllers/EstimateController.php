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
use App\Models\RoomTemplate;
use App\Models\Setting;
use App\Services\AnalyticsService;
use App\Services\EstimateService;
use App\Services\PdfRenderingService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstimateController extends Controller
{
    protected $estimateService;

    public function __construct(EstimateService $estimateService)
    {
        $this->estimateService = $estimateService;
    }

    /**
     * Display a listing of estimates.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Estimate::class);

        $query = Estimate::with(['client', 'sections'])->current()->latest();

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

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $estimates = $query->paginate(15);

        $counts = [
            'all' => Estimate::count(),
            'draft' => Estimate::where('status', Estimate::STATUS_DRAFT)->count(),
            'waiting_approval' => Estimate::where('status', Estimate::STATUS_WAITING_APPROVAL)->count(),
            'approved' => Estimate::where('status', Estimate::STATUS_APPROVED)->count(),
            'sent' => Estimate::where('status', Estimate::STATUS_SENT)->count(),
            'accepted' => Estimate::where('status', Estimate::STATUS_ACCEPTED)->count(),
            'declined' => Estimate::where('status', Estimate::STATUS_DECLINED)->count(),
        ];

        return view('estimates.index', compact('estimates', 'counts'));
    }

    /**
     * Show the form for creating a new estimate.
     */
    public function create()
    {
        $this->authorize('create', Estimate::class);

        $products = Product::with(['images', 'options.values'])->get();
        $templates = RoomTemplate::all();

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

        $templates->transform(function ($t) use ($templateProducts) {
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
        });
        $packages = ItemPackage::all();
        $clients = Client::orderBy('name')->get();
        $approvalChains = ApprovalChain::activeWithSteps();
        $pdfTemplates = PdfTemplate::where('is_active', true)->get();

        $settings = Setting::getAllCached();

        $defaults = [
            'currency' => $settings['currency_code'] ?? 'USD',
            'tax_1_name' => $settings['tax_1_name'] ?? 'Tax 1',
            'tax_1_rate' => $settings['tax_1_rate'] ?? 0,
            'tax_2_name' => $settings['tax_2_name'] ?? 'Tax 2',
            'tax_2_rate' => $settings['tax_2_rate'] ?? 0,
            'terms' => $settings['estimate_terms'] ?? '',
            'client_note' => $settings['estimate_client_note'] ?? '',
        ];

        return view('estimates.create', compact('products', 'templates', 'packages', 'defaults', 'clients', 'approvalChains', 'pdfTemplates'));
    }

    /**
     * Store a newly created estimate in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Estimate::class);

        $validated = $request->validate([

            'client_id' => 'required|integer',
            'estimate_date' => 'required|date',
            'expiry_date' => 'nullable|date',
            'status' => 'required|in:' . implode(',', [
                Estimate::STATUS_DRAFT,
                Estimate::STATUS_SENT,
                Estimate::STATUS_ACCEPTED,
                Estimate::STATUS_DECLINED,
                Estimate::STATUS_EXPIRED,
            ]),
            'currency' => 'required|string|max:10',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'client_note' => 'nullable|string',
            'admin_note' => 'nullable|string',
            'terms' => 'nullable|string',
            'pdf_theme' => 'nullable|string',
            'pdf_template_id' => 'nullable|exists:pdf_templates,id',
            'coupon_code_id' => 'nullable|exists:coupon_codes,id',
            'type' => 'required|in:standard,room_based',
            'items.*.internal_note' => 'nullable|string',
            'sections.*.items.*.internal_note' => 'nullable|string',
        ]);

        $validated['estimate_number'] = $this->estimateService->generateNextNumber();
        $validated['created_by'] = auth()->id();

        DB::beginTransaction();
        try {
            $estimate = Estimate::create($validated);

            if ($estimate->type === 'room_based' && $request->has('sections')) {
                foreach ($request->sections as $sectionIndex => $sectionData) {
                    $section = $estimate->sections()->create([
                        'name' => $sectionData['name'],
                        'order_index' => $sectionIndex,
                    ]);

                    if (isset($sectionData['items'])) {
                        foreach ($sectionData['items'] as $itemIndex => $itemData) {
                            $oi = $itemData['order_index'] ?? $itemIndex;
                            $this->estimateService->createEstimateItem($estimate, $section->id, $itemData, $oi);
                        }
                    }
                }
            } elseif ($request->has('items')) {
                foreach ($request->items as $itemIndex => $itemData) {
                    $oi = $itemData['order_index'] ?? $itemIndex;
                    $this->estimateService->createEstimateItem($estimate, null, $itemData, $oi);
                }
            }

            $this->estimateService->recalculateTotals($estimate);

            ActivityLog::log('created', $estimate, "Estimate #{$estimate->estimate_number} created by " . auth()->user()->name);

            DB::commit();

            return redirect()->route('estimates.show', $estimate)->with('success', 'Estimate created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->withErrors(['error' => 'Failed to create estimate: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified estimate.
     */
    public function show(Estimate $estimate)
    {
        $this->authorize('view', $estimate);

        $estimate->load('items.product.images', 'sections.items.product.images', 'approvals.user', 'checklistItems', 'creator');
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
        $templates = RoomTemplate::all();
        $packages = ItemPackage::all();
        $clients = Client::orderBy('name')->get();
        $approvalChains = ApprovalChain::activeWithSteps();
        $pdfTemplates = PdfTemplate::where('is_active', true)->get();
        $estimate->load(['sections.items.product.images', 'items.product.images', 'couponCode']);

        $settings = Setting::getAllCached();
        $defaults = [
            'currency' => $settings['currency_code'] ?? 'USD',
            'tax_1_name' => $settings['tax_1_name'] ?? 'Tax 1',
            'tax_1_rate' => $settings['tax_1_rate'] ?? 0,
            'tax_2_name' => $settings['tax_2_name'] ?? 'Tax 2',
            'tax_2_rate' => $settings['tax_2_rate'] ?? 0,
            'terms' => $settings['estimate_terms'] ?? '',
            'client_note' => $settings['estimate_client_note'] ?? '',
        ];

        return view('estimates.edit', compact('estimate', 'products', 'templates', 'packages', 'clients', 'approvalChains', 'pdfTemplates', 'defaults'));
    }

    /**
     * Update the specified estimate in storage.
     */
    public function update(Request $request, Estimate $estimate)
    {
        $this->authorize('update', $estimate);

        $validated = $request->validate([

            'client_id' => 'required|integer',
            'estimate_date' => 'required|date',
            'expiry_date' => 'nullable|date',
            'status' => 'required|in:' . implode(',', [
                Estimate::STATUS_DRAFT,
                Estimate::STATUS_SENT,
                Estimate::STATUS_ACCEPTED,
                Estimate::STATUS_DECLINED,
                Estimate::STATUS_EXPIRED,
            ]),
            'currency' => 'required|string|max:10',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'client_note' => 'nullable|string',
            'admin_note' => 'nullable|string',
            'terms' => 'nullable|string',
            'pdf_theme' => 'nullable|string',
            'pdf_template_id' => 'nullable|exists:pdf_templates,id',
            'coupon_code_id' => 'nullable|exists:coupon_codes,id',
            'type' => 'required|in:standard,room_based',
            'items.*.internal_note' => 'nullable|string',
            'sections.*.items.*.internal_note' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $isBranched = false;

            // Debugging Branching Logic
            \Illuminate\Support\Facades\Log::info('Estimate Update Branch Check', [
                'user_id' => auth()->id(),
                'can_edit' => $estimate->userCanEdit(auth()->user()),
                'is_creator' => auth()->id() === $estimate->created_by,
                'has_admin_role' => auth()->user()->hasRole(['admin', 'super_admin', 'super-admin']), // checking both for safety
                'estimate_id' => $estimate->id,
                'is_current' => $estimate->is_current_version
            ]);

            // Collaborative Editing: Check for Follower Edit
            if ($estimate->userCanEdit(auth()->user()) && auth()->id() !== $estimate->created_by && !auth()->user()->hasRole(['admin', 'super_admin', 'super-admin'])) {
                // Determine if we need to branch off to a new version (Proposal)
                // If this is ALREADY a non-current version (proposal), just update it.
                // If this is the CURRENT version, duplicate it first.

                if ($estimate->is_current_version) {
                    // Create new "Proposal Version" - SKIP item duplication, MARK as PROPOSAL (not live)
                    $newVersion = $this->estimateService->createVersion($estimate, false, true);
                    $estimate = $newVersion; // Switch context to new version
                    $isBranched = true;

                    // Notify Creator
                    $creator = $estimate->creator;
                    if ($creator) {
                        $creator->notify(new \App\Notifications\EstimateProposalNotification($estimate, auth()->user()));
                    }
                }
            }

            $estimate->update($validated);

            if ($estimate->type === 'room_based' && $request->has('sections')) {
                // Sync Sections
                $inputSectionIds = array_filter(array_column($request->sections, 'id'));
                $estimate->sections()->whereNotIn('id', $inputSectionIds)->delete();

                foreach ($request->sections as $sectionIndex => $sectionData) {
                    // If we branched, we treat all inputs as NEW (ignore old IDs)
                    if ($isBranched) {
                        $sectionData['id'] = null;
                        if (isset($sectionData['items'])) {
                            foreach ($sectionData['items'] as &$i)
                                $i['id'] = null;
                        }
                    }

                    if (!empty($sectionData['id'])) {
                        $section = $estimate->sections()->where('id', $sectionData['id'])->first();
                        $section->update([
                            'name' => $sectionData['name'],
                            'order_index' => $sectionIndex,
                        ]);
                    } else {
                        $section = $estimate->sections()->create([
                            'name' => $sectionData['name'],
                            'order_index' => $sectionIndex,
                        ]);
                    }

                    // Sync Items within Section
                    if (isset($sectionData['items'])) {
                        // If branched, inputIds logic for delete is fine (deletes nothing from empty estimate)
                        // But we must handle the loop carefully.
                        // The loop uses $sectionData['items'], which we potentially modified above by ref if passed by ref?
                        // No, we modified a copy or ref? Foreach loop variable $sectionData is a copy unless &$sectionData.
                        // Wait, my modification above `foreach ($sectionData['items'] as &$i)` only modifies the array inside the loop variable $sectionData.
                        // So I need to use that modified $sectionData.

                        $itemsToProcess = $sectionData['items'];

                        // If NOT branched, we need to do the delete logic.
                        if (!$isBranched) {
                            $inputItemIds = array_filter(array_column($itemsToProcess, 'id'));
                            $section->items()->whereNotIn('id', $inputItemIds)->delete();
                        }

                        foreach ($itemsToProcess as $itemIndex => $itemData) {
                            $oi = $itemData['order_index'] ?? $itemIndex;
                            if (!empty($itemData['id'])) {
                                $item = $estimate->items()->where('id', $itemData['id'])->first();
                                $this->estimateService->updateEstimateItem($item, $section->id, $itemData, $oi);
                            } else {
                                $this->estimateService->createEstimateItem($estimate, $section->id, $itemData, $oi);
                            }
                        }
                    } else {
                        $section->items()->delete();
                    }
                }
            } elseif ($request->has('items')) {
                // Sync Items
                $inputItemIds = array_filter(array_column($request->items, 'id'));
                $estimate->items()->whereNotIn('id', $inputItemIds)->delete();

                foreach ($request->items as $itemIndex => $itemData) {
                    if ($isBranched)
                        $itemData['id'] = null; // Sanitize ID

                    $oi = $itemData['order_index'] ?? $itemIndex;
                    if (!empty($itemData['id'])) {
                        $item = $estimate->items()->where('id', $itemData['id'])->first();
                        $this->estimateService->updateEstimateItem($item, null, $itemData, $oi);
                    } else {
                        $this->estimateService->createEstimateItem($estimate, null, $itemData, $oi);
                    }
                }
            }

            $this->estimateService->recalculateTotals($estimate);

            if ($isBranched) {
                ActivityLog::log('created_proposal', $estimate, "Proposed changes (v{$estimate->version}) by " . auth()->user()->name);
            } else {
                ActivityLog::log('updated', $estimate, "Estimate #{$estimate->estimate_number} updated by " . auth()->user()->name);
            }

            DB::commit();

            return redirect()->route('estimates.show', $estimate)->with('success', 'Estimate updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->withErrors(['error' => 'Failed to update estimate: ' . $e->getMessage()]);
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

        $validStatuses = [
            Estimate::STATUS_DRAFT,
            Estimate::STATUS_SENT,
            Estimate::STATUS_ACCEPTED,
            Estimate::STATUS_DECLINED,
            Estimate::STATUS_EXPIRED,
        ];

        if (!in_array($status, $validStatuses)) {
            return back()->with('error', 'Invalid status.');
        }

        $oldStatus = $estimate->status;
        $estimate->update(['status' => $status]);

        ActivityLog::log('status_updated', $estimate, "Estimate #{$estimate->estimate_number} status manually changed from {$oldStatus} to {$status}.");

        return back()->with('success', 'Estimate marked as ' . ucfirst($status) . '.');
    }

    /**
     * Revert the estimate to draft status.
     */
    public function revertToDraft(Estimate $estimate)
    {
        $this->authorize('revertToDraft', $estimate);

        $estimate->update(['status' => Estimate::STATUS_DRAFT]);

        ActivityLog::log('status_updated', $estimate, "Estimate #{$estimate->estimate_number} reverted to draft.");

        return back()->with('success', 'Estimate reverted to draft.');
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

        $estimateNumber = $estimate->estimate_number;
        $estimate->delete();

        ActivityLog::log('estimate_deleted', $estimate, "Estimate #{$estimateNumber} deleted by " . auth()->user()->name);

        return redirect()->route('estimates.index')->with('success', 'Estimate deleted successfully.');
    }
    public function approveVersion(Estimate $estimate)
    {
        $this->authorize('update', $estimate);

        // Strictly allow only Creator or Admins to approve (not followers with edit rights)
        if (auth()->id() !== $estimate->created_by && !auth()->user()->hasRole(['super_admin', 'admin'])) {
            abort(403, 'Only the creator or an admin can approve changes.');
        }

        // 1. Mark this version as current
        $estimate->update(['is_current_version' => true]);

        // 2. Find the root parent
        $parentId = $estimate->parent_id ?? $estimate->id;

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

        return back()->with('success', 'Follower added.');
    }

    public function removeFollower(Estimate $estimate, \App\Models\User $user)
    {
        $this->authorize('update', $estimate);

        $estimate->manualFollowers()->where('user_id', $user->id)->delete();

        return back()->with('success', 'Follower removed.');
    }
}
