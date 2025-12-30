<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalChain extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

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
}
