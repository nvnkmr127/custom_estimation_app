<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\ApprovalChain;
use App\Models\Estimate;
use App\Models\EstimateItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EstimateService
{
    public function __construct()
    {
    }

    /**
     * Create a new estimate item.
     */
    public function createEstimateItem(Estimate $estimate, ?int $sectionId, array $itemData, int $orderIndex): EstimateItem
    {
        $unitPrice = $itemData['unit_price'];
        $originalPrice = null;
        $isComplimentary = isset($itemData['is_complimentary']) && $itemData['is_complimentary'];

        if ($isComplimentary) {
            $originalPrice = $unitPrice;
            $unitPrice = 0;
        }

        $itemSubtotal = $unitPrice * $itemData['quantity'];
        $total = $itemSubtotal;

        return $estimate->items()->create([
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
            'internal_note' => $itemData['internal_note'] ?? null,
            'unit_type_id' => $itemData['unit_type_id'] ?? null,
            'options' => $itemData['options'] ?? null,
        ]);
    }

    /**
     * Update an existing estimate item.
     */
    public function updateEstimateItem(EstimateItem $item, ?int $sectionId, array $itemData, int $orderIndex): void
    {
        $unitPrice = $itemData['unit_price'];
        $originalPrice = null;
        $isComplimentary = isset($itemData['is_complimentary']) && $itemData['is_complimentary'];

        if ($isComplimentary) {
            $originalPrice = $unitPrice;
            $unitPrice = 0;
        }

        $itemSubtotal = $unitPrice * $itemData['quantity'];
        $total = $itemSubtotal;

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
            'internal_note' => $itemData['internal_note'] ?? null,
            'unit_type_id' => $itemData['unit_type_id'] ?? null,
            'options' => $itemData['options'] ?? null,
        ]);
    }

    /**
     * Recalculate and update the totals for an estimate.
     */
    public function recalculateTotals(Estimate $estimate): void
    {
        $estimate->load('items');

        $subtotal = $estimate->items->sum(function ($item) {
            return $item->unit_price * $item->quantity;
        });

        $totalTax = ($subtotal * ($estimate->tax_1 + $estimate->tax_2)) / 100;

        $discountTotal = 0;
        if ($estimate->discount_value > 0) {
            if ($estimate->discount_type === 'percentage') {
                $discountTotal = $subtotal * ($estimate->discount_value / 100);
            } else {
                $discountTotal = $estimate->discount_value;
            }
        }

        $grandTotal = ($subtotal + $totalTax) - $discountTotal - ($estimate->coupon_discount ?? 0);

        // --- Approval Chain Logic ---
        $chainToAssign = null;
        $totalDiscountAmount = $discountTotal + ($estimate->coupon_discount ?? 0);
        $discountPercentage = ($subtotal > 0) ? ($totalDiscountAmount / $subtotal) * 100 : 0;

        // 1. Check for Discount-based Approval (Highest Priority)
        $discountChain = ApprovalChain::where('is_active', true)
            ->whereNotNull('min_discount_percentage')
            ->where('min_discount_percentage', '<=', $discountPercentage)
            ->orderBy('min_discount_percentage', 'desc')
            ->first();

        if ($discountChain) {
            $chainToAssign = $discountChain;
        } else {
            // 2. Check for Amount-based Approval
            $chainToAssign = ApprovalChain::where('is_active', true)
                ->where(function ($q) use ($grandTotal) {
                    $q->whereNull('min_amount')->orWhere('min_amount', '<=', $grandTotal);
                })
                ->where(function ($q) use ($grandTotal) {
                    $q->whereNull('max_amount')->orWhere('max_amount', '>=', $grandTotal);
                })
                ->orderBy('min_amount', 'desc') // Pick highest tier if multiple overlap
                ->first();
        }

        $estimate->update([
            'subtotal' => $subtotal,
            'total_tax' => $totalTax,
            'discount_total' => $discountTotal,
            'grand_total' => $grandTotal,
            'approval_chain_id' => $chainToAssign ? $chainToAssign->id : null,
        ]);
    }

    /**
     * Create a new version of an estimate.
     */
    public function createVersion(Estimate $estimate, bool $replicateItems = true, bool $isProposal = false): Estimate
    {
        return DB::transaction(function () use ($estimate, $replicateItems, $isProposal) {
            // 1. Handle is_current_version
            if (!$isProposal) {
                $estimate->update(['is_current_version' => false]);
            }

            // 2. Replicate Estimate
            $newEstimate = $estimate->replicate();

            // Calculate next version number based on FAMILY maximum
            $rootId = $estimate->parent_id ?? $estimate->id;
            $maxVersion = Estimate::where('id', $rootId)
                ->orWhere('parent_id', $rootId)
                ->max('version');

            $newEstimate->version = ($maxVersion ?? $estimate->version) + 1;

            // If it's a proposal, it's NOT current yet.
            // If it's a standard version bump (unlikely direct flow here, but generic), it is current.
            $newEstimate->is_current_version = !$isProposal;

            // If it's the first version, the parent is the original.
            // If it's already a child, the parent stays the same.
            $newEstimate->parent_id = $estimate->parent_id ?? $estimate->id;

            // Generate new number with -vX suffix
            $baseNumber = preg_replace('/-v\d+$/', '', $estimate->estimate_number);
            $newEstimate->estimate_number = $baseNumber . '-v' . $newEstimate->version;

            $newEstimate->status = Estimate::STATUS_DRAFT; // Reset status
            $newEstimate->push();

            // 3. Replicate Sections and Items
            if ($replicateItems) {
                $this->duplicateEstimateItems($estimate, $newEstimate);
            }

            return $newEstimate;
        });
    }

    /**
     * Copy an existing estimate to a new one.
     */
    public function copy(Estimate $estimate): Estimate
    {
        return DB::transaction(function () use ($estimate) {
            $newEstimate = $estimate->replicate([
                'estimate_number',
                'status',
                'signature',
                'signed_at',
                'signer_ip',
                'email_opened_at',
                'last_viewed_at',
                'view_count',
            ]);

            // Generate new estimate number
            $newEstimate->estimate_number = $this->generateNextNumber();

            $newEstimate->status = Estimate::STATUS_DRAFT;
            $newEstimate->version = 1;
            $newEstimate->save();

            // Copy sections and items
            $this->duplicateEstimateItems($estimate, $newEstimate);

            ActivityLog::log('copied', $newEstimate, "Estimate #{$newEstimate->estimate_number} was created by copying #{$estimate->estimate_number}");

            return $newEstimate;
        });
    }

    /**
     * Duplicate sections and items from one estimate to another.
     */
    public function duplicateEstimateItems(Estimate $source, Estimate $target): void
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
     * Send estimate to client via email.
     */
    public function sendToClient(Estimate $estimate): bool
    {
        if (!$estimate->client || !$estimate->client->email) {
            throw new \Exception('Client does not have a valid email address.');
        }

        try {
            // Notify client (Refactored to use Event)
            // $estimate->client->notify(new \App\Notifications\EstimateSentToClient($estimate));

            // Dispatch Event instead of direct email
            event(new \App\Core\Events\Estimates\EstimateSent(
                $estimate->id,
                auth()->id() ?? 0, // Fallback for system actions
                'email'
            ));

            // Update status if it was draft
            if ($estimate->status === Estimate::STATUS_DRAFT) {
                $estimate->update(['status' => Estimate::STATUS_SENT]);
            }

            ActivityLog::create([
                'action' => 'sent_to_client',
                'description' => 'Estimate sent to client via email',
                'subject_type' => Estimate::class,
                'subject_id' => $estimate->id,
                'causer_type' => auth()->check() ? get_class(auth()->user()) : null,
                'causer_id' => auth()->id(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send estimate to client', [
                'estimate_id' => $estimate->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate the next available estimate number.
     */
    public function generateNextNumber(): string
    {
        $year = date('Y');
        $prefix = "EST-{$year}-";

        // Get recent estimates to find the highest sequence number
        $estimates = Estimate::where('estimate_number', 'like', "{$prefix}%")
            ->orderByRaw('LENGTH(estimate_number) DESC')
            ->orderBy('estimate_number', 'desc')
            ->limit(10)
            ->get();

        $maxSequence = 0;

        foreach ($estimates as $estimate) {
            $parts = explode('-', $estimate->estimate_number);
            // Handle version suffixes like -v2 by stripping them first if needed, 
            // but the regex approach below is safer for finding the base numeric part.
            // Assuming standard format EST-YYYY-XXX

            // Allow for version suffixes in the check
            $baseNumber = preg_replace('/-v\d+$/', '', $estimate->estimate_number);
            $parts = explode('-', $baseNumber);
            $suffix = end($parts);

            if (is_numeric($suffix)) {
                $sequence = (int) $suffix;
                if ($sequence > $maxSequence) {
                    $maxSequence = $sequence;
                }
            }
        }

        $nextSequence = $maxSequence + 1;

        // Loop to ensure uniqueness (handling race conditions and skipped numbers)
        do {
            $candidate = $prefix . str_pad($nextSequence, 3, '0', STR_PAD_LEFT);
            $exists = Estimate::where('estimate_number', $candidate)->exists();
            if ($exists) {
                $nextSequence++;
            }
        } while ($exists);

        return $candidate;
    }
}
