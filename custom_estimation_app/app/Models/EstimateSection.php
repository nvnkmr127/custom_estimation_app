<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstimateSection extends Model
{
    protected $fillable = [
        'estimate_id',
        'name',
        'order_index',
        'subtotal',
    ];

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
