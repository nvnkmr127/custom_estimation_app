<?php

namespace App\Services;

use App\Models\Estimate;
use App\Models\Setting;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;

class RescueNegotiationService
{
    protected $estimateService;

    public function __construct(EstimateService $estimateService)
    {
        $this->estimateService = $estimateService;
    }

    public function attemptRescue(Estimate $estimate, int $discountPercentage)
    {
        // 1. Safety Check: Global Limit
        $maxDiscount = Setting::getCached('nurture_rescue_discount_limit', 10);
        if ($discountPercentage > $maxDiscount) {
            throw new \Exception("Requested discount ({$discountPercentage}%) exceeds global safety limit ({$maxDiscount}%).");
        }

        // 2. Safety Check: Don't rescue if already rescued or accepted
        if ($estimate->status === 'accepted' || $estimate->status === 'declined') {
            return null;
        }

        return DB::transaction(function () use ($estimate, $discountPercentage) {
            // 3. Cooldown Check: Prevent Rescue Spam
            $rootId = $estimate->parent_id ?? $estimate->id;
            $familyIds = Estimate::where('id', $rootId)
                ->orWhere('parent_id', $rootId)
                ->pluck('id');
                
            $lastRescue = ActivityLog::whereIn('subject_id', $familyIds)
                ->where('action', 'system_rescue_offer')
                ->where('created_at', '>', now()->subDays(7))
                ->first();
            
            if ($lastRescue) {
                return null; // Silent skip during cooldown
            }

            // 4. Create New Version
            $newVersion = $this->estimateService->createVersion($estimate);

            // 5. Apply Discount - Multi-layer Logic
            // If estimate already has a percentage discount, we make it additive
            if ($newVersion->discount_type === 'percentage') {
                $newVersion->discount_value = min(100, $newVersion->discount_value + $discountPercentage);
            } else {
                // Convert existing fixed discount to percentage and layer the rescue percentage
                $existingFixed = (float) ($newVersion->discount_value ?? 0);
                $subtotal = (float) ($estimate->subtotal ?? 0);
                $existingPercentage = $subtotal > 0 ? ($existingFixed / $subtotal) * 100 : 0;
                
                $newVersion->discount_type = 'percentage';
                $newVersion->discount_value = min(100, $existingPercentage + $discountPercentage);
            }

            // Add a special admin note
            $newVersion->admin_note .= "\n[System Auto-Rescue] Created with {$discountPercentage}% discount.";
            $newVersion->save();

            // 5. Recalculate Totals
            $this->estimateService->recalculateTotals($newVersion);

            // 6. Finalize and Send to Client
            // Ensure status is at least approved to allow sending
            $newVersion->estimate_status = Estimate::EST_STATUS_APPROVED;
            $newVersion->save();

            $this->estimateService->sendToClient($newVersion);

            ActivityLog::create([
                'action' => 'system_rescue_offer',
                'description' => "System auto-generated Rescue Version v{$newVersion->version} with {$discountPercentage}% discount.",
                'subject_type' => Estimate::class,
                'subject_id' => $newVersion->id,
            ]);

            return $newVersion;
        });
    }
}
