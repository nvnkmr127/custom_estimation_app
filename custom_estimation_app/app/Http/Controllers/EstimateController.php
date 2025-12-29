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
            'approved' => Estimate::where('status', 'approved')->count(), // approved status not in constants list but kept for compatibility
            'sent' => Estimate::where('status', Estimate::STATUS_SENT)->count(),
            'accepted' => Estimate::where('status', Estimate::STATUS_ACCEPTED)->count(),
            'declined' => Estimate::where('status', Estimate::STATUS_DECLINED)->count(),
        ];

        return view('estimates.index', compact('estimates', 'counts'));
    }

    public function create()
    {
        $this->authorize('create', Estimate::class);

        $products = Product::with('images')->get();
        $templates = RoomTemplate::all();
        $packages = ItemPackage::all();
        $clients = \App\Models\Client::orderBy('name')->get();
        $approvalChains = \App\Models\ApprovalChain::where('is_active', true)->with('steps.user')->get();

        $settings = \App\Models\Setting::pluck('value', 'key');

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
                Estimate::STATUS_EXPIRED
            ]),
            'currency' => 'required|string|max:10',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'client_note' => 'nullable|string',
            'admin_note' => 'nullable|string',
            'terms' => 'nullable|string',
            'pdf_theme' => 'nullable|string|in:modern,classic,minimal',
        ]);

        $validated['estimate_number'] = 'EST-' . (Estimate::max('id') + 1001); // Simple number generation

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
                            $this->createEstimateItem($estimate, $section->id, $itemData, $oi);
                        }
                    }
                }
            } elseif ($request->has('items')) { // Standard Estimate
                foreach ($request->items as $itemIndex => $itemData) {
                    $oi = $itemData['order_index'] ?? $itemIndex;
                    $this->createEstimateItem($estimate, null, $itemData, $oi);
                }
            }

            $this->recalculateTotals($estimate);
            $estimate->save(); // Save totals

            DB::commit();
            return redirect()->route('estimates.show', $estimate)->with('success', 'Estimate created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create estimate: ' . $e->getMessage()]);
        }
    }

    public function show(Estimate $estimate)
    {
        $this->authorize('view', $estimate);

        $estimate->load('items.product.images', 'sections.items.product.images', 'approvals.user');
        $checklists = \App\Models\ApprovalChecklist::all();
        $declineReasons = \App\Models\DeclineReason::where('is_active', true)->get();

        // fetch version history
        $root = $estimate->parent ?? $estimate;
        $allVersions = Estimate::where('id', $root->id)->orWhere('parent_id', $root->id)->orderBy('version', 'desc')->get();

        return view('estimates.show', compact('estimate', 'checklists', 'declineReasons', 'allVersions'));
    }

    public function edit(Estimate $estimate)
    {
        $this->authorize('update', $estimate);

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
                Estimate::STATUS_EXPIRED
            ]),
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
                                $this->updateEstimateItem($item, $section->id, $itemData, $oi);
                            } else {
                                $this->createEstimateItem($estimate, $section->id, $itemData, $oi);
                            }
                        }
                    } else {
                        $section->items()->delete();
                    }
                }
            } elseif ($request->has('items')) { // Standard Estimate
                // Sync Items
                $inputItemIds = array_filter(array_column($request->items, 'id'));
                $estimate->items()->whereNotIn('id', $inputItemIds)->delete();

                foreach ($request->items as $itemIndex => $itemData) {
                    $oi = $itemData['order_index'] ?? $itemIndex;
                    if (!empty($itemData['id'])) {
                        $item = $estimate->items()->where('id', $itemData['id'])->first();
                        $this->updateEstimateItem($item, null, $itemData, $oi);
                    } else {
                        $this->createEstimateItem($estimate, null, $itemData, $oi);
                    }
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

    private function updateEstimateItem(EstimateItem $item, $sectionId, $itemData, $orderIndex)
    {
        $unitPrice = $itemData['unit_price'];
        $originalPrice = null;
        $isComplimentary = isset($itemData['is_complimentary']) && $itemData['is_complimentary'];

        if ($isComplimentary) {
            $originalPrice = $unitPrice;
            $unitPrice = 0;
        }

        $itemSubtotal = $unitPrice * $itemData['quantity'];
        $tax1Amount = $itemSubtotal * ($itemData['tax_1'] ?? 0) / 100;
        $tax2Amount = $itemSubtotal * ($itemData['tax_2'] ?? 0) / 100;
        $total = $itemSubtotal + $tax1Amount + $tax2Amount;

        $item->update([
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
            'length' => $itemData['length'] ?? null,
            'width' => $itemData['width'] ?? null,
            'formula' => $itemData['formula'] ?? null,
        ]);
    }

    private function createEstimateItem(Estimate $estimate, $sectionId, $itemData, $orderIndex)
    {
        $unitPrice = $itemData['unit_price'];
        $originalPrice = null;
        $isComplimentary = isset($itemData['is_complimentary']) && $itemData['is_complimentary'];

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
            'length' => $itemData['length'] ?? null,
            'width' => $itemData['width'] ?? null,
            'formula' => $itemData['formula'] ?? null,
        ]);
    }

    private function recalculateTotals(Estimate $estimate)
    {
        $subtotal = $estimate->items()->sum(DB::raw('unit_price * quantity'));
        $totalWithTax = $estimate->items()->sum('total');
        if ($estimate->discount_value > 0) {
            if ($estimate->discount_type === 'percentage') {
                $discountAmount = $subtotal * ($estimate->discount_value / 100);
            } else {
                $discountAmount = $estimate->discount_value;
            }
        } else {
            $discountAmount = 0;
        }

        $grandTotal = $totalWithTax - $discountAmount;

        // Persist logic here if needed
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

        $newEstimate->status = Estimate::STATUS_DRAFT; // Reset status
        $newEstimate->push();

        // 3. Replicate Sections and Items using shared method
        $this->duplicateEstimateItems($estimate, $newEstimate);

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

        $newEstimate->status = Estimate::STATUS_DRAFT;
        $newEstimate->version = 1;
        $newEstimate->save();

        // Copy sections and items using shared method
        $this->duplicateEstimateItems($estimate, $newEstimate);

        \App\Models\ActivityLog::log('copied', $newEstimate, "Estimate #{$newEstimate->estimate_number} was created by copying #{$estimate->estimate_number}");

        return redirect()->route('estimates.edit', $newEstimate)->with('success', 'Estimate copied successfully.');
    }

    /**
     * Helper to duplicate sections and items from one estimate to another
     */
    private function duplicateEstimateItems(Estimate $source, Estimate $target)
    {
        $source->load(['sections.items', 'items']);

        // Copy sections and their items
        foreach ($source->sections as $section) {
            $newSection = $section->replicate();
            $newSection->estimate_id = $target->id;
            $newSection->save();

            foreach ($section->items as $item) {
                $newItem = $item->replicate();
                $newItem->estimate_section_id = $newSection->id;
                $newItem->estimate_id = $target->id;
                $newItem->save();
            }
        }

        // Handle standalone items if any
        foreach ($source->items as $item) {
            if (!$item->estimate_section_id) {
                $newItem = $item->replicate();
                $newItem->estimate_id = $target->id;
                $newItem->save();
            }
        }
    }

    /**
     * Manually update the status of an estimate
     */
    public function markAs(Request $request, Estimate $estimate, $status)
    {
        $this->authorize('update', $estimate);

        $validStatuses = [
            Estimate::STATUS_DRAFT,
            Estimate::STATUS_SENT,
            Estimate::STATUS_ACCEPTED,
            Estimate::STATUS_DECLINED,
            Estimate::STATUS_EXPIRED
        ];

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
        if ($estimate->status === Estimate::STATUS_DRAFT) {
            $estimate->update(['status' => Estimate::STATUS_SENT]);
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

