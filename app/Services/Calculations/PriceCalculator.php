<?php

namespace App\Services\Calculations;

use App\Models\Estimate;
use App\Models\ApprovalChain;
use App\Models\Setting;

class PriceCalculator
{
    /**
     * Calculate all totals for an estimate.
     * Returns a structured array of calculated values, ready for persistence.
     * Does NOT perform database writes.
     * 
     * @param Estimate $estimate
     * @return array
     */
    public function calculate(Estimate $estimate): array
    {
        // Ensure items are loaded
        if (!$estimate->relationLoaded('items')) {
            $estimate->load('items');
        }

        // Fetch Tax Calculation Method
        // Options: 'subtotal_minus_discount' (Default), 'subtotal_only' (Gross)
        $taxMethod = Setting::getCached('tax_calculation_method', 'subtotal_minus_discount');

        // 1. Calculate Line Item Totals
        // Returns [ 'subtotal' => float, 'total_cost' => float, 'section_subtotals' => array, 'item_updates' => array ]
        $lines = $this->calculateLines($estimate);

        $subtotal = $lines['subtotal'];
        $totalCost = $lines['total_cost'];
        $sectionSubtotals = $lines['section_subtotals'];

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

        // 3. Coupon & Transportation
        $couponAmount = round($estimate->coupon_discount ?? 0, 2);
        $transportation = round($estimate->transportation_charges ?? 0, 2);

        // 4. Calculate Tax Base
        $taxableAmount = ($taxMethod === 'subtotal_only')
            ? $subtotal
            : ($subtotal - $discountTotal);
        
        $taxableAmount = max(0, $taxableAmount);

        // 5. Calculate Tax
        // User requested one tax only (GST)
        $tax1Amount = round($taxableAmount * ($estimate->tax_1 / 100), 2);
        $totalTax = $tax1Amount;

        // 6. Grand Total
        $grandTotal = round(($subtotal - $discountTotal) + $totalTax + $transportation - $couponAmount, 2);
        $grandTotal = max(0, $grandTotal);

        // 7. Margin/Profit
        $netRevenue = $subtotal - $discountTotal;
        $grossProfit = $netRevenue - $totalCost;

        // 8. Approval Chain Logic
        $approvalChainId = $this->determineApprovalChain($subtotal, $discountTotal, $couponAmount, $grandTotal);

        return [
            'estimate_updates' => [
                'subtotal' => $subtotal,
                'total_tax' => $totalTax,
                'discount_total' => $discountTotal,
                'grand_total' => $grandTotal,
                'total_cost' => $totalCost,
                'gross_profit' => $grossProfit,
                'approval_chain_id' => $approvalChainId,
            ],
            'section_updates' => $sectionSubtotals, // [section_id => amount]
            'item_updates' => $lines['item_updates'], // [item_id => total]
        ];
    }

    private function calculateLines(Estimate $estimate): array
    {
        $rawSubtotal = 0;
        $rawTotalCost = 0;
        $sectionSubtotals = [];
        $itemUpdates = [];

        foreach ($estimate->items as $item) {
            $sizeMultiplier = 1;
            if (!empty($item->formula) && in_array($item->formula, ['area', 'volume', 'area_lh', 'formula'])) {
                $s = (float) ($item->size ?? 1);
                if ($s > 0)
                    $sizeMultiplier = $s;
            }

            // Financial Precision: Maintain raw floats for accumulation to avoid cent-drift
            $lineTotalRaw = ($item->unit_price * $item->quantity * $sizeMultiplier);
            $lineCostRaw = (($item->cost ?? 0) * $item->quantity * $sizeMultiplier);

            $rawSubtotal += $lineTotalRaw;
            $rawTotalCost += $lineCostRaw;

            // Track for sections (rounded for display)
            if ($item->estimate_section_id) {
                if (!isset($sectionSubtotals[$item->estimate_section_id])) {
                    $sectionSubtotals[$item->estimate_section_id] = 0;
                }
                $sectionSubtotals[$item->estimate_section_id] += round($lineTotalRaw, 2);
            }

            // Track item total for update check (rounded per business rule)
            $itemUpdates[$item->id] = round($lineTotalRaw, 2);
        }

        return [
            'subtotal' => round($rawSubtotal, 2),
            'total_cost' => round($rawTotalCost, 2),
            'section_subtotals' => $sectionSubtotals,
            'item_updates' => $itemUpdates,
        ];
    }

    private function determineApprovalChain($subtotal, $discountTotal, $couponAmount, $grandTotal): ?int
    {
        // Discount % logic: Based on gross subtotal
        $discountPercentage = ($subtotal > 0) ? (($discountTotal + $couponAmount) / $subtotal) * 100 : 0;
        $discountPercentage = round($discountPercentage, 2);

        // 7. Tiered Approval Logic - Select most restrictive/highest authority chain
        $candidates = [];

        // 7.1 Discount-based matches
        $discountChain = ApprovalChain::where('is_active', true)
            ->whereNotNull('min_discount_percentage')
            ->where('min_discount_percentage', '<=', $discountPercentage)
            ->orderBy('min_discount_percentage', 'desc')
            ->first();
        if ($discountChain) $candidates[] = $discountChain;

        // 7.2 Amount-based matches
        $amountChain = ApprovalChain::where('is_active', true)
            ->whereNotNull('min_amount')
            ->where('min_amount', '<=', $grandTotal)
            ->where(function($q) use ($grandTotal) {
                $q->whereNull('max_amount')->orWhere('max_amount', '>=', $grandTotal);
            })
            ->orderBy('min_amount', 'desc')
            ->first();
        if ($amountChain) $candidates[] = $amountChain;

        if (empty($candidates)) return null;

        // Bias towards strictness: If multiple constraints apply, select the most authoritative (highest minimum)
        usort($candidates, fn($a, $b) => ($b->min_amount ?? 0) <=> ($a->min_amount ?? 0));
        return $candidates[0]->id;
    }
}
