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

        $products = Product::with('images')->get();
        $templates = RoomTemplate::all();
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
            'title' => 'nullable|string|max:255',
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

        $estimate->load('items.product.images', 'sections.items.product.images', 'approvals.user');
        $checklists = ApprovalChecklist::getAllCached();
        $declineReasons = DeclineReason::getActiveCached();

        // fetch version history
        $root = $estimate->parent ?? $estimate;
        $allVersions = Estimate::where('id', $root->id)->orWhere('parent_id', $root->id)->orderBy('version', 'desc')->get();

        return view('estimates.show', compact('estimate', 'checklists', 'declineReasons', 'allVersions'));
    }

    /**
     * Show the form for editing the specified estimate.
     */
    public function edit(Estimate $estimate)
    {
        $this->authorize('update', $estimate);

        $products = Product::with('images')->get();
        $templates = RoomTemplate::all();
        $packages = ItemPackage::all();
        $clients = Client::orderBy('name')->get();
        $approvalChains = ApprovalChain::activeWithSteps();
        $pdfTemplates = PdfTemplate::where('is_active', true)->get();
        $estimate->load(['sections.items.product.images', 'items.product.images']);

        return view('estimates.edit', compact('estimate', 'products', 'templates', 'packages', 'clients', 'approvalChains', 'pdfTemplates'));
    }

    /**
     * Update the specified estimate in storage.
     */
    public function update(Request $request, Estimate $estimate)
    {
        $this->authorize('update', $estimate);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
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
        ]);

        DB::beginTransaction();
        try {
            $estimate->update($validated);

            if ($estimate->type === 'room_based' && $request->has('sections')) {
                // Sync Sections
                $inputSectionIds = array_filter(array_column($request->sections, 'id'));
                $estimate->sections()->whereNotIn('id', $inputSectionIds)->delete();

                foreach ($request->sections as $sectionIndex => $sectionData) {
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
                        $inputItemIds = array_filter(array_column($sectionData['items'], 'id'));
                        $section->items()->whereNotIn('id', $inputItemIds)->delete();

                        foreach ($sectionData['items'] as $itemIndex => $itemData) {
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
        $estimate->load(['sections.items.product', 'items.product', 'client']);

        $theme = $estimate->pdf_theme ?: 'modern';

        return view("estimates.print_{$theme}", compact('estimate'));
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
}
