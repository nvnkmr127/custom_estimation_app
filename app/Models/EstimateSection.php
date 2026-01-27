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
        'is_package',
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
