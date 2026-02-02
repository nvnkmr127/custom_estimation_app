<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class EstimateItem extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'estimate_id',
        'estimate_section_id',
        'product_id',
        'name',
        'description',
        'size',
        'unit_price',
        'cost',
        'quantity',
        'unit_type',
        'unit_type_id',
        'tax_1',
        'tax_2',
        'total',
        'order_index',
        'is_complimentary',
        'original_price',
        'length',
        'width',
        'height',
        'formula',
        'internal_note',
        'options',
        'is_package',
        'original_item_id',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'cost',
        'internal_note',
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

    public function unitType()
    {
        return $this->belongsTo(UnitType::class, 'unit_type_id');
    }

    public function comments()
    {
        return $this->morphMany(EstimateComment::class, 'commentable');
    }
}
