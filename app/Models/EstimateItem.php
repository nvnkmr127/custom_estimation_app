<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstimateItem extends Model
{
    protected $fillable = [
        'estimate_id',
        'estimate_section_id',
        'product_id',
        'name',
        'description',
        'size',
        'unit_price',
        'quantity',
        'unit_type',
        'tax_1',
        'tax_2',
        'total',
        'order_index',
        'is_complimentary',
        'original_price',
        'length',
        'width',
        'formula',
        'internal_note',
        'options',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function estimate()
    {
        return $this->belongsTo(Estimate::class);
    }

    public function section()
    {
        return $this->belongsTo(EstimateSection::class, 'estimate_section_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function comments()
    {
        return $this->morphMany(EstimateComment::class, 'commentable');
    }
}
