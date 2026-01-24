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
    private $dispatcher;

    public function __construct(\App\Core\Events\EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Create a new estimate with sections and items.
     * 
     * @param array $data Input data for estimate
     * @param array $itemsOrSections Data for items or sections
     * @param string $type 'standard' or 'room_based'
     * @return Estimate
     * @throws \Exception
     */
    public function createEstimate(array $data, array $itemsOrSections, string $type): Estimate
    {
        $attempts = 0;
        $maxAttempts = 3;
        $lastException = null;

        while ($attempts < $maxAttempts) {
            $attempts++;

            $data['estimate_number'] = $this->generateNextNumber();
            $data['created_by'] = auth()->id();

            DB::beginTransaction();
            try {
                $estimate = Estimate::create($data);

                if ($type === 'room_based') {
                    foreach ($itemsOrSections as $sectionIndex => $sectionData) {
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
                } else {
                    // Standard
                    foreach ($itemsOrSections as $itemIndex => $itemData) {
                        $oi = $itemData['order_index'] ?? $itemIndex;
                        $this->createEstimateItem($estimate, null, $itemData, $oi);
                    }
                }

                $this->recalculateTotals($estimate);

                ActivityLog::log('created', $estimate, "Estimate #{$estimate->estimate_number} created by " . auth()->user()->name);

                DB::commit();

                // Dispatch Event
                $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateCreated($estimate, auth()->id()));

                return $estimate;

            } catch (\Exception $e) {
                DB::rollBack();

                // If unique constraint violation, retry
                if (str_contains($e->getMessage(), '23000') || str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                    $lastException = $e;
                    continue;
                }
                throw $e;
            }
        }

        throw new \Exception('Failed to create estimate (Unique Number Collision) after multiple attempts. Please try again.');
    }

    /**
     * Update an estimate.
     * 
     * @param Estimate $estimate
     * @param array $data Validated estimate data
     * @param array $itemsOrSections Data for items or sections
     * @param string $type
     * @param bool $forceBranch Force a new version creation
     * @return Estimate The updated or new estimate (if branched)
     */
    public function updateEstimate(Estimate $estimate, array $data, array $itemsOrSections, string $type, bool $forceBranch = false): Estimate
    {
        DB::beginTransaction();
        try {
            $isBranched = false;
            $originalNumber = $estimate->estimate_number;

            // Strict State Locking & Forced Versioning Logic
            $isFinalized = in_array($estimate->status, [
                Estimate::STATUS_APPROVED,
                Estimate::STATUS_SENT,
                Estimate::STATUS_ACCEPTED,
                Estimate::STATUS_DECLINED,
                Estimate::STATUS_EXPIRED
            ]);

            $needsBranching = $forceBranch || $isFinalized;

            // Also apply existing collaborator logic
            if ($estimate->is_current_version && auth()->id() !== $estimate->created_by && !auth()->user()->hasRole(['admin', 'super_admin', 'super-admin'])) {
                $needsBranching = true;
            }

            if ($needsBranching) {
                // Create new "Proposal Version" - SKIP item duplication because we will save the form data
                $newVersion = $this->createVersion($estimate, false, true);

                // Switch context to new version
                $estimate = $newVersion;
                $isBranched = true;

                // Notify Creator
                if (auth()->id() !== $estimate->created_by && $estimate->creator) {
                    $estimate->creator->notify(new \App\Notifications\EstimateProposalNotification($estimate, auth()->user()));
                }
            }

            $estimate->update($data);

            // Sync Sections/Items
            if ($type === 'room_based') {
                $inputSectionIds = array_filter(array_column($itemsOrSections, 'id'));

                // If branched, we don't delete from old estimate, but new estimate is empty anyway.
                // If NOT branched, we verify deletes.
                if (!$isBranched) {
                    $estimate->sections()->whereNotIn('id', $inputSectionIds)->delete();
                }

                foreach ($itemsOrSections as $sectionIndex => $sectionData) {
                    // Sanitize IDs if branched
                    if ($isBranched) {
                        $sectionData['id'] = null;
                        if (isset($sectionData['items'])) {
                            foreach ($sectionData['items'] as &$i)
                                $i['id'] = null;
                        }
                    }

                    if (!empty($sectionData['id'])) {
                        $section = $estimate->sections()->where('id', $sectionData['id'])->first();
                        if ($section) {
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
                    } else {
                        $section = $estimate->sections()->create([
                            'name' => $sectionData['name'],
                            'order_index' => $sectionIndex,
                        ]);
                    }

                    // Sync Items
                    if (isset($sectionData['items'])) {
                        $itemsToProcess = $sectionData['items'];
                        if (!$isBranched) {
                            $inputItemIds = array_filter(array_column($itemsToProcess, 'id'));
                            $section->items()->whereNotIn('id', $inputItemIds)->delete();
                        }

                        foreach ($itemsToProcess as $itemIndex => $itemData) {
                            $oi = $itemData['order_index'] ?? $itemIndex;
                            if (!empty($itemData['id']) && !$isBranched) {
                                $item = $estimate->items()->where('id', $itemData['id'])->first();
                                if ($item) {
                                    $this->updateEstimateItem($item, $section->id, $itemData, $oi);
                                } else {
                                    $this->createEstimateItem($estimate, $section->id, $itemData, $oi);
                                }
                            } else {
                                $this->createEstimateItem($estimate, $section->id, $itemData, $oi);
                            }
                        }
                    } else {
                        $section->items()->delete();
                    }
                }
            } else {
                // Standard Type
                $itemsToProcess = $itemsOrSections;
                if ($isBranched) {
                    // Clean IDs
                    foreach ($itemsToProcess as &$i)
                        $i['id'] = null;
                }

                if (!$isBranched) {
                    $inputItemIds = array_filter(array_column($itemsToProcess, 'id'));
                    $estimate->items()->whereNotIn('id', $inputItemIds)->delete();
                }

                foreach ($itemsToProcess as $itemIndex => $itemData) {
                    $oi = $itemData['order_index'] ?? $itemIndex;
                    if (!empty($itemData['id']) && !$isBranched) {
                        $item = $estimate->items()->where('id', $itemData['id'])->first();
                        if ($item) {
                            $this->updateEstimateItem($item, null, $itemData, $oi);
                        } else {
                            $this->createEstimateItem($estimate, null, $itemData, $oi);
                        }
                    } else {
                        $this->createEstimateItem($estimate, null, $itemData, $oi);
                    }
                }
            }

            $this->recalculateTotals($estimate);

            if ($isBranched) {
                ActivityLog::log('created_proposal', $estimate, "Created revision v{$estimate->version} from locked/shared estimate {$originalNumber}.");
            } else {
                ActivityLog::log('updated', $estimate, "Estimate #{$estimate->estimate_number} updated by " . auth()->user()->name);
            }

            DB::commit();

            // Dispatch Event
            $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateUpdated($estimate, auth()->id(), $estimate->getChanges()));

            return $estimate;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create a new estimate item.
     */
    public function createEstimateItem(Estimate $estimate, ?int $sectionId, array $itemData, int $orderIndex): EstimateItem
    {
        $unitPrice = round($itemData['unit_price'], 2);
        // Cost: Default to 0 if not provided
        $cost = isset($itemData['cost']) ? round($itemData['cost'], 2) : 0;
        $originalPrice = null;
        $isComplimentary = isset($itemData['is_complimentary']) && $itemData['is_complimentary'];

        if ($isComplimentary) {
            $originalPrice = $unitPrice;
            $unitPrice = 0;
        }

        $itemSubtotal = round($unitPrice * $itemData['quantity'], 2);
        $total = $itemSubtotal;

        return $estimate->items()->create([
            'estimate_section_id' => $sectionId,
            'product_id' => $itemData['product_id'] ?? null,
            'name' => $itemData['name'],
            'description' => $itemData['description'] ?? null,
            'unit_price' => $unitPrice,
            'cost' => $cost,
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
            'height' => $itemData['height'] ?? null,
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
        $unitPrice = round($itemData['unit_price'], 2);
        $cost = isset($itemData['cost']) ? round($itemData['cost'], 2) : $item->cost ?? 0;
        $originalPrice = null;
        $isComplimentary = isset($itemData['is_complimentary']) && $itemData['is_complimentary'];

        if ($isComplimentary) {
            $originalPrice = $unitPrice;
            $unitPrice = 0;
        }

        $itemSubtotal = round($unitPrice * $itemData['quantity'], 2);
        $total = $itemSubtotal;

        $item->update([
            'estimate_section_id' => $sectionId,
            'product_id' => $itemData['product_id'] ?? null,
            'name' => $itemData['name'],
            'description' => $itemData['description'] ?? null,
            'unit_price' => $unitPrice,
            'cost' => $cost,
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
            'height' => $itemData['height'] ?? null,
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

        // Fetch Tax Calculation Method
        // Options: 'subtotal_minus_discount' (Default), 'subtotal_only' (Gross)
        $taxMethod = \App\Models\Setting::getCached('tax_calculation_method', 'subtotal_minus_discount');

        // 1. Calculate Line Item Totals with Rounding
        $subtotal = 0;
        $totalCost = 0; // NEW: Track Cost

        foreach ($estimate->items as $item) {
            $lineTotal = round($item->unit_price * $item->quantity, 2);
            $lineCost = round(($item->cost ?? 0) * $item->quantity, 2);

            $subtotal += $lineTotal;
            $totalCost += $lineCost;

            // Ensure item's cached total is correct
            if (abs($item->total - $lineTotal) > 0.001) {
                // Determine if we should save this. 
                // Fix the item total if it's wrong in DB to ensure consistency
                $item->total = $lineTotal;
                $item->saveQuietly(); // Use saveQuietly to avoid triggering events if possible, or just save()
            }
        }
        $subtotal = round($subtotal, 2);

        // 2. Calculate Discount
        $discountTotal = 0;
        if ($estimate->discount_value > 0) {
            if ($estimate->discount_type === 'percentage') {
                $discountTotal = round($subtotal * ($estimate->discount_value / 100), 2);
            } else {
                $discountTotal = round($estimate->discount_value, 2);
            }
        }

        // Ensure discount doesn't exceed subtotal
        $discountTotal = min($discountTotal, $subtotal);

        // 3. Calculate Tax Base
        if ($taxMethod === 'subtotal_only') {
            // Tax calculated on Gross Subtotal
            $taxableAmount = $subtotal;
        } else {
            // Default: Tax calculated on Net (Subtotal - Discount)
            $taxableAmount = $subtotal - $discountTotal;
        }

        // 4. Calculate Tax
        // We use the global estimate tax rates for now as per current schema design
        // If per-item tax is needed, we would sum($item->total * $item->tax_rate)
        $tax1Amount = round($taxableAmount * ($estimate->tax_1 / 100), 2);
        $tax2Amount = round($taxableAmount * ($estimate->tax_2 / 100), 2);
        $totalTax = $tax1Amount + $tax2Amount;

        // 5. Coupon (Applied after tax? Or before? Usually coupons are payment methods or pre-tax discounts)
        // Current logic treated it as a final deduction. Let's keep it but ensure rounding.
        $couponAmount = round($estimate->coupon_discount ?? 0, 2);

        $grandTotal = round(($subtotal - $discountTotal) + $totalTax - $couponAmount, 2);

        // Prevent negative total
        $grandTotal = max(0, $grandTotal);

        // 6. Calculate Margin/Profit
        // Margin = (Revenue - Cost) / Revenue * 100
        // Revenue here effectively is Net Subtotal (Subtotal - Discount - Coupon?)
        // Let's use simplified: (Subtotal - Discount) - Cost
        $netRevenue = $subtotal - $discountTotal;
        $grossProfit = $netRevenue - $totalCost;

        // --- Approval Chain Logic ---
        $chainToAssign = null;
        // Discount % logic: Based on gross subtotal
        $discountPercentage = ($subtotal > 0) ? (($discountTotal + $couponAmount) / $subtotal) * 100 : 0;
        $discountPercentage = round($discountPercentage, 2);

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

        $updateData = [
            'subtotal' => $subtotal,
            'total_tax' => $totalTax,
            'discount_total' => $discountTotal,
            'grand_total' => $grandTotal,
            'total_cost' => $totalCost,
            'gross_profit' => $grossProfit,
            'approval_chain_id' => $chainToAssign ? $chainToAssign->id : null,
        ];

        // If we had columns for cost/margin, we would save them here.
        // For now, we just calculated them.

        $estimate->update($updateData);
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
            // Dispatch Event instead of direct email
            event(new \App\Core\Events\Estimates\EstimateSent(
                $estimate->id,
                auth()->id() ?? 0, // Fallback for system actions
                'email'
            ));

            // Ensure estimate is approved before sending
            if (!in_array($estimate->status, [Estimate::STATUS_APPROVED, Estimate::STATUS_SENT])) {
                throw new \Exception('Estimate must be approved before it can be sent to the client.');
            }

            // Update status if it was approved
            if ($estimate->status === Estimate::STATUS_APPROVED) {
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
        $estimates = Estimate::withTrashed()
            ->where('estimate_number', 'like', "{$prefix}%")
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
            $exists = Estimate::withTrashed()->where('estimate_number', $candidate)->exists();
            if ($exists) {
                $nextSequence++;
            }
        } while ($exists);

        return $candidate;
    }
}
