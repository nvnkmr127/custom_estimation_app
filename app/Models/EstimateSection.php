<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class EstimateSection extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'estimate_id',
        'name',
        'order_index',
        'subtotal',
        'section_type',
    ];

    /**
     * Get the total value of the section (sum of items).
     * Note: 'subtotal' column might be used for caching, but dynamic calc is safer for consistency.
     */
    public function getTotalAttribute()
    {
        return $this->items->sum('total');
    }

    public function getIsPackageAttribute()
    {
        return $this->section_type === 'package' || $this->is_package_legacy;
    }

    public function getIsPackageLegacyAttribute()
    {
        // Fallback for any legacy check if needed, or just return false
        return false;
    }

    public function estimate()
    {
        return $this->belongsTo(Estimate::class);
    }

    public function items()
    {
        return $this->hasMany(EstimateItem::class)->orderBy('order_index');
    }

    public function comments()
    {
        return $this->morphMany(EstimateComment::class, 'commentable');
    }
}
