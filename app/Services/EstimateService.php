<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\ApprovalChain;
use App\Models\Estimate;
use App\Models\EstimateItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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
        // Simple Retry Loop not strictly needed for collisions anymore (resolved by DB Sequence), 
        // but kept for other transient DB errors.
        $attempts = 0;
        $maxAttempts = 3;

        // Ensure defaults for required fields to prevent NOT NULL violations
        $data['tax_1'] = $data['tax_1'] ?? 0;
        $data['tax_2'] = $data['tax_2'] ?? 0;
        $data['discount_value'] = $data['discount_value'] ?? 0;

        while ($attempts < $maxAttempts) {
            $attempts++;

            // Generate Number (Atomic, persistent via DB Locking)
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
                            'section_type' => $sectionData['section_type'] ?? (($sectionData['is_package'] ?? false) ? 'package' : 'room'),
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

                // Refined Error Handling: Only retry on ACTUAL Unique Constraint Violations
                // "UNIQUE constraint failed" (SQLite) or "Duplicate entry" (MySQL)
                $msg = $e->getMessage();
                if (str_contains($msg, 'UNIQUE constraint failed') || str_contains($msg, 'Duplicate entry')) {
                    Log::critical("Estimate Unique Collision DESPITE Sequence Logic: {$data['estimate_number']}", ['error' => $msg]);
                    usleep(100000);
                    continue;
                }

                // Re-throw generic or validation errors (like NOT NULL violations)
                throw $e;
            }
        }

        throw new \Exception("Failed to create estimate after {$maxAttempts} attempts.");
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
            // 1. History Integrity Check: Prevent editing of archived versions
            if (!$estimate->is_current_version) {
                throw new \Exception("You cannot edit an archived version of an estimate. Create a new version instead.");
            }

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

            // Logic Gap Fix: If estimate was rejected or has changes requested, editing it should reset approval status to 'draft'
            // so the user knows it is a work-in-progress again.
            if (in_array($estimate->approval_status, ['rejected', 'changes_requested'])) {
                $estimate->approval_status = 'draft';
                // We set property here, update() call below will persist it if we include it or separate call.
                // Since $data is passed to update(), we should explicitly update this field.
                $estimate->forceFill(['approval_status' => 'draft']);
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
                                'section_type' => $sectionData['section_type'] ?? (($sectionData['is_package'] ?? false) ? 'package' : 'room'),
                            ]);
                        } else {
                            $section = $estimate->sections()->create([
                                'name' => $sectionData['name'],
                                'order_index' => $sectionIndex,
                                'section_type' => $sectionData['section_type'] ?? (($sectionData['is_package'] ?? false) ? 'package' : 'room'),
                            ]);
                        }
                    } else {
                        $section = $estimate->sections()->create([
                            'name' => $sectionData['name'],
                            'order_index' => $sectionIndex,
                            'section_type' => $sectionData['section_type'] ?? (($sectionData['is_package'] ?? false) ? 'package' : 'room'),
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

        $sizeMultiplier = 1;
        if (!empty($itemData['formula']) && in_array($itemData['formula'], ['area', 'volume', 'area_lh', 'formula'])) {
            $s = (float) ($itemData['size'] ?? 1);
            if ($s > 0)
                $sizeMultiplier = $s;
        }

        $itemSubtotal = round($unitPrice * $itemData['quantity'] * $sizeMultiplier, 2);
        $total = $itemSubtotal;

        return $estimate->items()->create([
            'estimate_section_id' => $sectionId,
            'product_id' => $itemData['product_id'] ?? null,
            'name' => $itemData['name'],
            'description' => $itemData['description'] ?? null,
            'unit_price' => $unitPrice,
            'cost' => $cost,
            'quantity' => $itemData['quantity'],
            'size' => $itemData['size'] ?? null,
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
            'is_package' => $itemData['is_package'] ?? false,
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

        $sizeMultiplier = 1;
        if (!empty($itemData['formula']) && in_array($itemData['formula'], ['area', 'volume', 'area_lh', 'formula'])) {
            $s = (float) ($itemData['size'] ?? 1);
            if ($s > 0)
                $sizeMultiplier = $s;
        }

        $itemSubtotal = round($unitPrice * $itemData['quantity'] * $sizeMultiplier, 2);
        $total = $itemSubtotal;

        $item->update([
            'estimate_section_id' => $sectionId,
            'product_id' => $itemData['product_id'] ?? null,
            'name' => $itemData['name'],
            'description' => $itemData['description'] ?? null,
            'unit_price' => $unitPrice,
            'cost' => $cost,
            'quantity' => $itemData['quantity'],
            'size' => $itemData['size'] ?? null,
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
            'is_package' => $itemData['is_package'] ?? false,
        ]);
    }

    /**
     * Recalculate and update the totals for an estimate.
     */
    /**
     * Recalculate and update the totals for an estimate.
     */
    public function recalculateTotals(Estimate $estimate): void
    {
        $calculator = new \App\Services\Calculations\PriceCalculator();
        $results = $calculator->calculate($estimate);

        // 1. Persist Item Updates (if changed)
        // We iterate through currently loaded items to update them in memory and DB if needed
        foreach ($estimate->items as $item) {
            if (isset($results['item_updates'][$item->id])) {
                $newTotal = $results['item_updates'][$item->id];
                if (abs($item->total - $newTotal) > 0.001) {
                    $item->total = $newTotal;
                    $item->saveQuietly();
                }
            }
        }

        // 2. Persist Section Updates
        foreach ($results['section_updates'] as $sectionId => $amount) {
            \App\Models\EstimateSection::where('id', $sectionId)->update(['subtotal' => round($amount, 2)]);
        }

        // 3. Persist Estimate Totals
        $updateData = $results['estimate_updates'];

        // Logic Check: Auto-approve if no chain assigned?
        if (!$updateData['approval_chain_id'] && $estimate->status === Estimate::STATUS_WAITING_APPROVAL) {
            $updateData['status'] = Estimate::STATUS_APPROVED;
            $updateData['approval_status'] = 'approved';
        }

        $estimate->update($updateData);
    }

    /**
     * Create a new version of an estimate.
     */
    public function createVersion(Estimate $estimate, bool $replicateItems = true, bool $isProposal = false): Estimate
    {
        return DB::transaction(function () use ($estimate, $replicateItems, $isProposal) {
            // Lock the family to prevent concurrent version generation
            $rootId = $estimate->parent_id ?? $estimate->id;

            // Acquire lock on all versions in this family to ensure max('version') is stable
            // We select 'id' to minimize data but ensure rows are locked.
            Estimate::where('id', $rootId)
                ->orWhere('parent_id', $rootId)
                ->lockForUpdate()
                ->get(['id']);

            // 1. Handle is_current_version
            // Always mark the old version as not current to ensure the new draft is visible (The "Active" version)
            $updateData = ['is_current_version' => false];

            // If the old version was SENT, expire it so the client cannot accept it while we are working on a V2.
            if ($estimate->status === Estimate::STATUS_SENT) {
                $updateData['status'] = Estimate::STATUS_EXPIRED;
            }

            $estimate->update($updateData);

            // 2. Replicate Estimate
            $newEstimate = $estimate->replicate();

            // Calculate next version number based on FAMILY maximum
            $maxVersion = Estimate::where('id', $rootId)
                ->orWhere('parent_id', $rootId)
                ->max('version');

            $newEstimate->version = ($maxVersion ?? $estimate->version) + 1;

            // The new version becomes the current, active draft.
            $newEstimate->is_current_version = true;

            // If it's the first version, the parent is the original.
            // If it's already a child, the parent stays the same.
            $newEstimate->parent_id = $estimate->parent_id ?? $estimate->id;

            // Generate new number with -vX suffix
            $baseNumber = preg_replace('/-v\d+$/', '', $estimate->estimate_number);
            $newEstimate->estimate_number = $baseNumber . '-v' . $newEstimate->version;

            $newEstimate->status = Estimate::STATUS_DRAFT; // Reset status
            $newEstimate->approval_status = 'draft'; // Reset approval status
            $newEstimate->created_by = auth()->id() ?? $estimate->created_by; // Set creator to current user

            // Fix: Reset dates for new version to avoid legacy expiry issues
            $newEstimate->estimate_date = now();
            $newEstimate->expiry_date = null;

            // Clear state fields for new version
            $newEstimate->signature = null;
            $newEstimate->signed_at = null;
            $newEstimate->signer_ip = null;
            $newEstimate->view_count = 0;
            $newEstimate->last_viewed_at = null;
            $newEstimate->converted_at = null;
            $newEstimate->invoice_id = null;
            $newEstimate->perfex_proposal_id = null; // Detach from previous Perfex proposal to create a new one

            $newEstimate->push();

            // 3. Replicate Sections and Items
            if ($replicateItems) {
                $this->duplicateEstimateItems($estimate, $newEstimate);
            }

            // 4. Replicate Manual Followers
            foreach ($estimate->manualFollowers as $follower) {
                $newEstimate->manualFollowers()->create([
                    'user_id' => $follower->user_id,
                    'permissions' => $follower->permissions, // Permissions casted to array in model, handled by Eloquent
                ]);
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

            // Generate new estimate number (Uses new V2 Logic automatically)
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
                // Track lineage: preserve original ID if exists, otherwise this is the original
                $newItem->original_item_id = $item->original_item_id ?? $item->id;
                $newItem->save();
            }
        }

        // Handle standalone items if any
        foreach ($source->items as $item) {
            if (!$item->estimate_section_id) {
                $newItem = $item->replicate();
                $newItem->estimate_id = $target->id;
                // Track lineage
                $newItem->original_item_id = $item->original_item_id ?? $item->id;
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
            // Ensure estimate is approved before sending
            if (!in_array($estimate->status, [Estimate::STATUS_APPROVED, Estimate::STATUS_SENT])) {
                throw new \Exception('Estimate must be approved before it can be sent to the client.');
            }

            // Dispatch Event instead of direct email
            event(new \App\Core\Events\Estimates\EstimateSent(
                $estimate,
                auth()->id() ?? 0, // Fallback for system actions
                'email'
            ));

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
     * Generate the next available estimate number using Database Sequence.
     * Uses Row-Level Locking on a settings table to ensure strict sequential uniqueness.
     */
    public function generateNextNumber(array $excludeNumbers = []): string
    {
        // Execute in a separate transaction to ensure the sequence updates are atomic and committed 
        // immediately, preventing race conditions even if the main transaction fails (resulting in gaps, not duplicates).
        return DB::transaction(function () {
            $year = date('Y');
            $key = "estimate_sequence_{$year}";
            $prefix = "EST-{$year}-";

            // Try to find and lock the sequence row
            $setting = \App\Models\Setting::where('key', $key)->lockForUpdate()->first();

            if ($setting) {
                // Simple Increment
                $next = (int) $setting->value + 1;
                $setting->update(['value' => $next]);
            } else {
                // Cold Start: Calculate from DB one last time to initialize the sequence
                $maxSequence = 0;

                // We scan strictly to seed the sequence
                $estimates = DB::table('estimates')
                    ->where('estimate_number', 'like', "{$prefix}%")
                    ->select('estimate_number')
                    ->get();

                foreach ($estimates as $est) {
                    // Extract numeric part
                    $numStr = str_replace($prefix, '', $est->estimate_number);
                    $numStr = preg_replace('/-v\d+$/', '', $numStr);

                    if (is_numeric($numStr)) {
                        $val = (int) $numStr;
                        if ($val > $maxSequence)
                            $maxSequence = $val;
                    }
                }

                $next = $maxSequence + 1;

                // Create the setting row
                \App\Models\Setting::create(['key' => $key, 'value' => $next]);
            }

            return $prefix . str_pad($next, 3, '0', STR_PAD_LEFT);
        });
    }
}
