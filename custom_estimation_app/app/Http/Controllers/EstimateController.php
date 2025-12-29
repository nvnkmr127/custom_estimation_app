<?php

namespace App\Http\Controllers;

use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\Product;
use App\Models\RoomTemplate;
use App\Models\ItemPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstimateController extends Controller
{
    public function index(Request $request)
    {
        $query = Estimate::with(['client', 'sections'])->current()->latest();

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $estimates = $query->paginate(15);
        $counts = [
            'all' => Estimate::count(),
            'draft' => Estimate::where('status', 'draft')->count(),
            'waiting_approval' => Estimate::where('status', 'waiting_approval')->count(),
            'approved' => Estimate::where('status', 'approved')->count(),
            'sent' => Estimate::where('status', 'sent')->count(),
            'accepted' => Estimate::where('status', 'accepted')->count(),
            'declined' => Estimate::where('status', 'declined')->count(),
        ];

        return view('estimates.index', compact('estimates', 'counts'));
    }

    public function create()
    {
        $products = Product::with('images')->get();
        $templates = RoomTemplate::all();
        $packages = ItemPackage::all();
        $clients = \App\Models\Client::orderBy('name')->get();
        $approvalChains = \App\Models\ApprovalChain::where('is_active', true)->with('steps.user')->get();

        $settings = \App\Models\Setting::pluck('value', 'key');
        // ... (rest of defaults) ...
        $defaults = [
            'currency' => $settings['currency_code'] ?? 'USD',
            'tax_1_name' => $settings['tax_1_name'] ?? 'Tax 1',
            'tax_1_rate' => $settings['tax_1_rate'] ?? 0,
            'tax_2_name' => $settings['tax_2_name'] ?? 'Tax 2',
            'tax_2_rate' => $settings['tax_2_rate'] ?? 0,
            'terms' => $settings['estimate_terms'] ?? '',
            'client_note' => $settings['estimate_client_note'] ?? '',
        ];

        return view('estimates.create', compact('products', 'templates', 'packages', 'defaults', 'clients', 'approvalChains'));
    }

    // ...

    public function show(Estimate $estimate)
    {
        $estimate->load('items.product.images', 'sections.items.product.images', 'approvals.user');
        $checklists = \App\Models\ApprovalChecklist::all();
        $declineReasons = \App\Models\DeclineReason::where('is_active', true)->get();
        return view('estimates.show', compact('estimate', 'checklists', 'declineReasons'));
    }

    // ...

    public function edit(Estimate $estimate)
    {
        $products = Product::with('images')->get();
        $templates = RoomTemplate::all();
        $packages = ItemPackage::all();
        $clients = \App\Models\Client::orderBy('name')->get();
        $approvalChains = \App\Models\ApprovalChain::where('is_active', true)->with('steps.user')->get();
        $estimate->load(['sections.items.product.images', 'items.product.images']);
        return view('estimates.edit', compact('estimate', 'products', 'templates', 'packages', 'clients', 'approvalChains'));
    }

    public function update(Request $request, Estimate $estimate)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'client_id' => 'required|integer',
            'estimate_date' => 'required|date',
            'expiry_date' => 'nullable|date',
            'status' => 'required|in:draft,sent,accepted,declined,expired',
            'currency' => 'required|string|max:10',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'client_note' => 'nullable|string',
            'admin_note' => 'nullable|string',
            'terms' => 'nullable|string',
            'pdf_theme' => 'nullable|string|in:modern,classic,minimal',
        ]);

        DB::beginTransaction();
        try {
            $estimate->update($validated);

            // Clear existing sections and items
            $estimate->sections()->delete();
            $estimate->items()->delete();

            // Recreate based on type
            if ($estimate->type === 'room_based' && $request->has('sections')) {
                foreach ($request->sections as $sectionIndex => $sectionData) {
                    $section = $estimate->sections()->create([
                        'name' => $sectionData['name'],
                        'order_index' => $sectionIndex,
                    ]);

                    if (isset($sectionData['items'])) {
                        foreach ($sectionData['items'] as $itemIndex => $itemData) {
                            $oi = $itemData['order_index'] ?? $itemIndex;
                            $this->createEstimateItem($estimate, $section->id, $itemData, $oi);
                        }
                    }
                }
            } elseif ($request->has('items')) {
                foreach ($request->items as $itemIndex => $itemData) {
                    $oi = $itemData['order_index'] ?? $itemIndex;
                    $this->createEstimateItem($estimate, null, $itemData, $oi);
                }
            }

            $this->recalculateTotals($estimate);

            DB::commit();
            return redirect()->route('estimates.show', $estimate)->with('success', 'Estimate updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update estimate: ' . $e->getMessage()]);
        }
    }

    public function destroy(Estimate $estimate)
    {
        $estimate->delete();
        return redirect()->route('estimates.index')->with('success', 'Estimate deleted successfully.');
    }

    /**
     * Duplicate an estimate item
     */
    public function duplicateItem(EstimateItem $item)
    {
        // Clone the item with all its properties
        $newItem = $item->replicate();
        $newItem->save();

        // Recalculate estimate totals
        $item->estimate->recalculateTotals();

        return response()->json([
            'success' => true,
            'message' => 'Item duplicated successfully',
            'item' => $newItem->load('section'),
        ]);
    }

    public function downloadPdf(Estimate $estimate, \App\Services\AnalyticsService $analytics)
    {
        // Track download
        $analytics->logAccess($estimate, 'download');

        $estimate->load(['sections.items', 'items', 'client']);
        $settings = \App\Models\Setting::pluck('value', 'key');

        // Check for Custom PDF Template
        if ($estimate->pdf_template_id && $estimate->pdfTemplate) {
            $template = $estimate->pdfTemplate;
            $service = new \App\Services\PdfRenderingService();

            // Attempt to use cached file
            $cachedPath = $service->renderAndCache($template, $estimate);

            if ($cachedPath && file_exists($cachedPath)) {
                return response()->download($cachedPath, 'estimate_' . $estimate->estimate_number . '.pdf');
            } else {
                return back()->with('error', 'PDF generation failed.');
            }
        }

        $theme = $estimate->pdf_theme ?: ($settings['pdf_theme'] ?? 'modern');
        $view = 'estimates.print_' . $theme;

        if (!view()->exists($view)) {
            $view = 'estimates.print_modern';
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, compact('estimate', 'settings'));

        // Default for standard templates
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('estimate_' . $estimate->estimate_number . '.pdf');
    }

    public function preview(Request $request)
    {
        // Create an ephemeral Estimate object from request data
        $estimateData = $request->all();
        $estimate = new Estimate($estimateData);
        $estimate->estimate_number = 'PREVIEW-' . date('Ymd');

        // Mock sections and items relationship for the view
        $sections = collect();
        if ($request->type === 'room_based' && $request->has('sections')) {
            foreach ($request->sections as $sIdx => $sData) {
                $section = new \App\Models\EstimateSection(['name' => $sData['name'], 'order_index' => $sIdx]);
                $section->id = $sIdx + 1; // Temporary ID
                $sectionItems = collect();
                if (isset($sData['items'])) {
                    foreach ($sData['items'] as $iIdx => $iData) {
                        $item = new \App\Models\EstimateItem($iData);
                        $item->order_index = $iIdx;
                        $sectionItems->push($item);
                    }
                }
                $section->setRelation('items', $sectionItems);
                $sections->push($section);
            }
        }
        $estimate->setRelation('sections', $sections);

        $items = collect();
        if ($request->type === 'standard' && $request->has('items')) {
            foreach ($request->items as $iIdx => $iData) {
                $item = new \App\Models\EstimateItem($iData);
                $item->order_index = $iIdx;
                $items->push($item);
            }
        }
        $estimate->setRelation('items', $items);

        // Mock client if present
        if ($request->client_id) {
            $estimate->setRelation('client', \App\Models\Client::find($request->client_id));
        }

        $settings = \App\Models\Setting::pluck('value', 'key');
        $theme = $estimate->pdf_theme ?: ($settings['pdf_theme'] ?? 'modern');
        $view = 'estimates.print_' . $theme;

        if (!view()->exists($view)) {
            $view = 'estimates.print_modern';
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, compact('estimate', 'settings'));
        return $pdf->stream($estimate->estimate_number . '.pdf');
    }

    private function createEstimateItem($estimate, $sectionId, $itemData, $orderIndex)
    {
        $unitPrice = $itemData['unit_price'];
        $originalPrice = null;
        $isComplimentary = isset($itemData['is_complimentary']) && $itemData['is_complimentary'];

        // If complimentary, store original price and set unit price to 0
        if ($isComplimentary) {
            $originalPrice = $unitPrice;
            $unitPrice = 0;
        }

        $itemSubtotal = $unitPrice * $itemData['quantity'];
        $tax1Amount = $itemSubtotal * ($itemData['tax_1'] ?? 0) / 100;
        $tax2Amount = $itemSubtotal * ($itemData['tax_2'] ?? 0) / 100;
        $total = $itemSubtotal + $tax1Amount + $tax2Amount;

        $estimate->items()->create([
            'estimate_section_id' => $sectionId,
            'product_id' => $itemData['product_id'] ?? null,
            'name' => $itemData['name'],
            'description' => $itemData['description'] ?? null,
            'unit_price' => $unitPrice,
            'quantity' => $itemData['quantity'],
            'unit_type' => $itemData['unit_type'] ?? 'nos',
            'tax_1' => $itemData['tax_1'] ?? 0,
            'tax_2' => $itemData['tax_2'] ?? 0,
            'total' => $total,
            'order_index' => $orderIndex,
            'is_complimentary' => $isComplimentary,
            'original_price' => $originalPrice,
        ]);
    }

    private function recalculateTotals($estimate)
    {
        $subtotal = 0;
        $totalTax = 0;

        foreach ($estimate->items as $item) {
            $itemSubtotal = $item->unit_price * $item->quantity;
            $subtotal += $itemSubtotal;
            $totalTax += ($itemSubtotal * $item->tax_1 / 100) + ($itemSubtotal * $item->tax_2 / 100);
        }

        // Calculate estimate-level discount
        $discountAmount = 0;
        if ($estimate->discount_value > 0) {
            if ($estimate->discount_type === 'percentage') {
                $discountAmount = $subtotal * ($estimate->discount_value / 100);
            } else {
                $discountAmount = $estimate->discount_value;
            }
        }

        // Calculate coupon discount
        $couponDiscount = 0;
        if ($estimate->coupon_code_id && $estimate->couponCode) {
            $subtotalAfterDiscount = $subtotal - $discountAmount;
            $couponDiscount = $estimate->couponCode->calculateDiscount($subtotalAfterDiscount);
        }

        $grandTotal = $subtotal + $totalTax - $discountAmount - $couponDiscount;

        $estimate->update([
            'subtotal' => $subtotal,
            'total_tax' => $totalTax,
            'discount_total' => $discountAmount,
            'coupon_discount' => $couponDiscount,
            'grand_total' => $grandTotal,
        ]);

        // Update section subtotals for room-based estimates
        if ($estimate->type === 'room_based') {
            foreach ($estimate->sections as $section) {
                $sectionSubtotal = $section->items->sum(function ($item) {
                    return $item->unit_price * $item->quantity;
                });
                $section->update(['subtotal' => $sectionSubtotal]);
            }
        }

        // Increment coupon usage if this is a new estimate with coupon
        if ($estimate->coupon_code_id && $estimate->couponCode && $estimate->wasRecentlyCreated) {
            $estimate->couponCode->incrementUsage();
        }
    }

    /**
     * Approval Workflow Methods
     */
    public function submitForApproval(Estimate $estimate)
    {
        if ($estimate->status !== 'draft') {
            return back()->with('error', 'Only drafts can be submitted.');
        }
        $estimate->update(['status' => 'waiting_approval']);
        return back()->with('success', 'Estimate submitted for approval.');
    }

    public function approve(Estimate $estimate)
    {
        if ($estimate->status !== 'waiting_approval') {
            return back()->with('error', 'Estimate is not waiting for approval.');
        }
        $estimate->update(['status' => 'approved']);

        // Log approval
        \App\Models\EstimateApproval::create([
            'estimate_id' => $estimate->id,
            'user_id' => auth()->id(),
            'status' => 'approved',
            'comments' => request('comments')
        ]);

        return back()->with('success', 'Estimate approved successfully.');
    }

    public function reject(Estimate $estimate)
    {
        if ($estimate->status !== 'waiting_approval') {
            return back()->with('error', 'Estimate is not waiting for approval.');
        }
        $estimate->update(['status' => 'draft']); // Revert to draft

        // Log rejection
        \App\Models\EstimateApproval::create([
            'estimate_id' => $estimate->id,
            'user_id' => auth()->id(),
            'status' => 'rejected',
            'comments' => request('comments')
        ]);

        return back()->with('success', 'Estimate rejected and reverted to draft.');
    }
    public function createVersion(Estimate $estimate)
    {
        // 1. Mark current as not current
        $estimate->update(['is_current_version' => false]);

        // 2. Replicate Estimate
        $newEstimate = $estimate->replicate();
        $newEstimate->version = $estimate->version + 1;
        $newEstimate->is_current_version = true;

        // If it's the first version, the parent is the original. 
        // If it's already a child, the parent stays the same.
        $newEstimate->parent_id = $estimate->parent_id ?? $estimate->id;

        // Generate new number with -vX suffix
        $baseNumber = preg_replace('/-v\d+$/', '', $estimate->estimate_number);
        $newEstimate->estimate_number = $baseNumber . '-v' . $newEstimate->version;

        $newEstimate->status = 'draft'; // Reset status
        $newEstimate->push(); // Save and push

        // 3. Replicate Sections and Items
        $estimate->load(['sections.items', 'items']);

        foreach ($estimate->sections as $section) {
            $newSection = $section->replicate();
            $newSection->estimate_id = $newEstimate->id;
            $newSection->save();

            foreach ($section->items as $item) {
                $newItem = $item->replicate();
                $newItem->estimate_section_id = $newSection->id;
                $newItem->estimate_id = $newEstimate->id;
                $newItem->save();
            }
        }

        // Handle standalone items if any (though usually in sections)
        foreach ($estimate->items as $item) {
            if (!$item->estimate_section_id) {
                $newItem = $item->replicate();
                $newItem->estimate_id = $newEstimate->id;
                $newItem->save();
            }
        }

        return redirect()->route('estimates.edit', $newEstimate)
            ->with('success', 'New version created! You are now editing version ' . $newEstimate->version);
    }

    /**
     * Copy an existing estimate to a new one
     */
    public function copy(Estimate $estimate)
    {
        $this->authorize('view', $estimate);

        $newEstimate = $estimate->replicate([
            'estimate_number',
            'status',
            'signature',
            'signed_at',
            'signer_ip',
            'email_opened_at',
            'last_viewed_at',
            'view_count'
        ]);

        // Generate new estimate number
        $lastEstimate = Estimate::orderBy('id', 'desc')->first();
        $nextNumber = $lastEstimate ? ((int) str_replace('EST-', '', $lastEstimate->estimate_number) + 1) : 1000;
        $newEstimate->estimate_number = 'EST-' . $nextNumber;

        $newEstimate->status = 'draft';
        $newEstimate->version = 1;
        $newEstimate->save();

        // Copy sections and items
        foreach ($estimate->sections as $section) {
            $newSection = $section->replicate();
            $newSection->estimate_id = $newEstimate->id;
            $newSection->save();

            foreach ($section->items as $item) {
                $newItem = $item->replicate();
                $newItem->estimate_section_id = $newSection->id;
                $newItem->estimate_id = $newEstimate->id;
                $newItem->save();
            }
        }

        \App\Models\ActivityLog::log('copied', $newEstimate, "Estimate #{$newEstimate->estimate_number} was created by copying #{$estimate->estimate_number}");

        return redirect()->route('estimates.edit', $newEstimate)->with('success', 'Estimate copied successfully.');
    }

    /**
     * Manually update the status of an estimate
     */
    public function markAs(Request $request, Estimate $estimate, $status)
    {
        $this->authorize('update', $estimate);

        $validStatuses = ['draft', 'sent', 'accepted', 'declined', 'expired'];

        if (!in_array($status, $validStatuses)) {
            return back()->with('error', 'Invalid status.');
        }

        $oldStatus = $estimate->status;
        $estimate->update(['status' => $status]);

        \App\Models\ActivityLog::log('status_updated', $estimate, "Estimate #{$estimate->estimate_number} status manually changed from {$oldStatus} to {$status}.");

        return back()->with('success', "Estimate marked as " . ucfirst($status) . ".");
    }

    /**
     * Send estimate to client via email
     */
    public function sendToClient(Estimate $estimate)
    {
        $this->authorize('update', $estimate);

        if (!$estimate->client || !$estimate->client->email) {
            return back()->with('error', 'Client does not have a valid email address.');
        }

        // Notify client
        $estimate->client->notify(new \App\Notifications\EstimateSentToClient($estimate));

        // Update status if it was draft
        if ($estimate->status === 'draft') {
            $estimate->update(['status' => 'sent']);
        }

        \App\Models\ActivityLog::log('sent_to_client', $estimate, "Estimate #{$estimate->estimate_number} was sent to {$estimate->client->email}.");

        return back()->with('success', 'Estimate sent to client successfully.');
    }
    public function batchDownload(Request $request)
    {
        $request->validate([
            'estimate_ids' => 'required|array',
            'estimate_ids.*' => 'exists:estimates,id',
        ]);

        // Dispatch Job
        \App\Jobs\GenerateBatchPdfZip::dispatch($request->estimate_ids, auth()->id());

        return back()->with('success', 'Batch export started. You will receive an email/notification when it is ready.');
    }
}
