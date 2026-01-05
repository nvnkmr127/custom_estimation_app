<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalChain extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
        'fallback_user_id',
        'min_amount',
        'max_amount',
        'currency',
    ];

    /**
     * Scope a query to include chains matching the amount criteria.
     */
    public function scopeMatches($query, $amount)
    {
        return $query->where('is_active', true)
            ->where(function ($q) use ($amount) {
                // Check min_amount (inclusive)
                $q->whereNull('min_amount')
                    ->orWhere('min_amount', '<=', $amount);
            })
            ->where(function ($q) use ($amount) {
                // Check max_amount (inclusive)
                $q->whereNull('max_amount')
                    ->orWhere('max_amount', '>=', $amount);
            });
    }

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function steps()
    {
        return $this->hasMany(ApprovalChainStep::class)->orderBy('order');
    }

    public function estimates()
    {
        return $this->hasMany(Estimate::class);
    }

    /**
     * Scope a query to only include active approval chains with their steps.
     */
    public function scopeActiveWithSteps($query)
    {
        return $query->where('is_active', true)->with('steps')->orderBy('name');
    }
    public function fallbackUser()
    {
        return $this->belongsTo(User::class, 'fallback_user_id');
    }
}
