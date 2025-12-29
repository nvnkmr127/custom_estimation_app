<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CouponCode extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'min_amount',
        'max_discount',
        'usage_limit',
        'usage_count',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
        'value' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_discount' => 'decimal:2',
    ];

    /**
     * Check if coupon is valid for given amount
     */
    public function isValid($estimateTotal = 0)
    {
        // Check if active
        if (!$this->is_active) {
            return false;
        }

        // Check usage limit
        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        // Check minimum amount
        if ($this->min_amount && $estimateTotal < $this->min_amount) {
            return false;
        }

        // Check validity dates
        $now = Carbon::now();

        if ($this->valid_from && $now->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $now->gt($this->valid_until)) {
            return false;
        }

        return true;
    }

    /**
     * Check if coupon can be used (not expired, not at limit)
     */
    public function canBeUsed()
    {
        return $this->isValid(0);
    }

    /**
     * Increment usage count
     */
    public function incrementUsage()
    {
        $this->increment('usage_count');
    }

    /**
     * Calculate discount amount for given total
     */
    public function calculateDiscount($amount)
    {
        if ($this->type === 'percentage') {
            $discount = ($amount * $this->value) / 100;
        } else {
            $discount = $this->value;
        }

        // Apply max discount cap if set
        if ($this->max_discount && $discount > $this->max_discount) {
            $discount = $this->max_discount;
        }

        // Don't exceed the amount
        if ($discount > $amount) {
            $discount = $amount;
        }

        return round($discount, 2);
    }

    /**
     * Get validation message
     */
    public function getValidationMessage($estimateTotal = 0)
    {
        if (!$this->is_active) {
            return 'This coupon code is no longer active.';
        }

        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return 'This coupon code has reached its usage limit.';
        }

        if ($this->min_amount && $estimateTotal < $this->min_amount) {
            return 'Minimum purchase amount of ₹' . number_format($this->min_amount, 2) . ' required.';
        }

        $now = Carbon::now();

        if ($this->valid_from && $now->lt($this->valid_from)) {
            return 'This coupon code is not yet valid.';
        }

        if ($this->valid_until && $now->gt($this->valid_until)) {
            return 'This coupon code has expired.';
        }

        return null;
    }

    /**
     * Relationship with estimates
     */
    public function estimates()
    {
        return $this->hasMany(Estimate::class);
    }
}
