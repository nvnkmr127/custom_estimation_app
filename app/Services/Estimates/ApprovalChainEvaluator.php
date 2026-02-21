<?php

namespace App\Services\Estimates;

use App\Models\ApprovalChain;
use App\Models\Estimate;

class ApprovalChainEvaluator
{
    /**
     * Evaluate and return the applicable approval chain for an estimate.
     * 
     * Priority:
     * 1. Discount-based chains (highest trigger percentage first)
     * 2. Amount-based chains (highest trigger amount first)
     * 
     * @param Estimate $estimate
     * @return ApprovalChain|null
     */
    public function evaluate(Estimate $estimate): ?ApprovalChain
    {
        $subtotal = $estimate->subtotal;
        $discountTotal = $estimate->discount_total ?? 0;
        $couponAmount = $estimate->coupon_discount ?? 0;
        $grandTotal = $estimate->grand_total;

        // 1. Calculate Effective Discount Percentage
        $discountPercentage = ($subtotal > 0) ? (($discountTotal + $couponAmount) / $subtotal) * 100 : 0;
        $discountPercentage = round($discountPercentage, 4); // Use higher precision for comparison

        // 2. Check Discount-based triggers
        // We find the chain with the HIGHEST min_discount_percentage that is still <= current discount
        /** @var ApprovalChain|null $discountChain */
        $discountChain = ApprovalChain::where('is_active', true)
            ->whereNotNull('min_discount_percentage')
            ->where('min_discount_percentage', '<=', (float) $discountPercentage)
            ->with([
                'steps' => function ($query) {
                    $query->orderBy('order');
                },
                'steps.user'
            ])
            ->orderBy('min_discount_percentage', 'desc')
            ->first();

        if ($discountChain) {
            return $discountChain;
        }

        // 3. Check Amount-based triggers
        // We find the chain with the HIGHEST min_amount that matches the grand total range
        /** @var ApprovalChain|null $amountChain */
        $amountChain = ApprovalChain::where('is_active', true)
            ->where(function ($q) use ($grandTotal) {
                $q->whereNull('min_amount')->orWhere('min_amount', '<=', $grandTotal);
            })
            ->where(function ($q) use ($grandTotal) {
                $q->whereNull('max_amount')->orWhere('max_amount', '>=', $grandTotal);
            })
            ->with([
                'steps' => function ($query) {
                    $query->orderBy('order');
                },
                'steps.user'
            ])
            ->orderBy('min_amount', 'desc')
            ->first();

        return $amountChain;
    }

    /**
     * Helper to just get the ordered steps if a chain matches.
     * 
     * @param Estimate $estimate
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    public function getApplicableSteps(Estimate $estimate)
    {
        $chain = $this->evaluate($estimate);
        return $chain ? $chain->steps : null;
    }
}
