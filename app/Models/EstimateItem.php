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
        'selected_options',
        'is_package',
        'original_item_id',
    ];

    protected $casts = [
        'options' => 'array',
        'selected_options' => 'array',
        'is_package' => 'boolean',
    ];

    /**
     * Mutator to ensure empty strings are saved as NULL for integer column.
     */
    public function setUnitTypeIdAttribute($value)
    {
        $this->attributes['unit_type_id'] = $value ?: null;
    }

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

    /**
     * Get the formatted label for the formula (e.g., "Area", "Volume").
     */
    public function getFormulaLabelAttribute()
    {
        $formula = $this->formula;

        if (empty($formula) || $formula === 'fixed') {
            // Auto-detect based on dimensions if not explicitly set
            // If height is present and > 0, assume Volume, otherwise Area (if L or W exist)
            if ($this->height > 0) {
                return 'Volume';
            }
            if ($this->length > 0 || $this->width > 0) {
                return 'Area';
            }
            return ''; // No dimensions
        }

        return ucfirst(str_replace('_', ' ', $formula));
    }

    /**
     * Get the full formatted configuration string.
     * Example: "Area (L: 10 x W: 5) Sqft"
     */
    public function getUnitConfigurationAttribute()
    {
        $parts = [];
        // Use +0 to remove trailing zeros from decimals (e.g. 10.00 -> 10)
        if (!empty($this->length))
            $parts[] = 'L: ' . ($this->length + 0);
        if (!empty($this->width))
            $parts[] = 'W: ' . ($this->width + 0);
        if (!empty($this->height))
            $parts[] = 'H: ' . ($this->height + 0);

        if (empty($parts)) {
            return '';
        }

        $label = $this->formula_label;
        $dims = implode(' x ', $parts);

        $config = "{$label} ({$dims})";

        if (!empty($this->unit_type)) {
            $config .= ' ' . $this->unit_type;
        }

        return $config;
    }

    /**
     * Get the calculated subtotal (Base Price * Quantity * Size Multiplier) before tax.
     */
    public function getCalculatedSubtotalAttribute()
    {
        $sizeMultiplier = 1;

        // If the item uses a formula (Area, Volume, etc.), the Size is a multiplier
        // Otherwise (Fixed, etc.), Size acts as 1 (or is ignored)
        if (!empty($this->formula) && in_array($this->formula, ['area', 'volume', 'area_lh', 'formula'])) {
            $s = (float) ($this->size ?? 1);
            if ($s > 0) {
                $sizeMultiplier = $s;
            }
        }

        return $this->unit_price * $this->quantity * $sizeMultiplier;
    }
}
