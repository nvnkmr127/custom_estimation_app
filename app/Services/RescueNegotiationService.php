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
            // 3. Create New Version
            $newVersion = $this->estimateService->createVersion($estimate);

            // 4. Apply Discount
            // We apply it as a global percentage discount
            $newVersion->discount_type = 'percentage';
            $newVersion->discount_value = $discountPercentage;

            // Add a special admin note
            $newVersion->admin_note .= "\n[System Auto-Rescue] Created with {$discountPercentage}% discount.";
            $newVersion->save();

            // 5. Recalculate Totals
            $this->estimateService->recalculateTotals($newVersion);

            // 6. Send to Client (Silent send, status update)
            // We use the boolean return of sendToClient but we might want custom email text.
            // EstimateService::sendToClient sends specific notification 'EstimateSentToClient'.
            // For Rescue, we usually want to send a specific "Offer" email.

            $newVersion->update(['status' => 'sent']);

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
