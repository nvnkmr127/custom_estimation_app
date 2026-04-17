<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Setting;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'sku',
        'unit_price',
        'unit_type',
        'unit_type_id',
        'calculation_method',
        'status',
        'is_featured',
        'sort_order',
        'suggested_by',
        'retired_at',
        'retirement_reason',
        'attributes',
        'dimensions',
        'tags',
        'tax_1',
        'tax_2',
        'formula',
    ];

    protected $casts = [
        'attributes' => 'array',
        'dimensions' => 'array',
        'tags' => 'array',
        'is_featured' => 'boolean',
        'retired_at' => 'datetime',
    ];

    /**
     * Mutator to ensure empty strings are saved as NULL for integer column.
     */
    public function setUnitTypeIdAttribute($value)
    {
        $this->attributes['unit_type_id'] = $value ?: null;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeRetired($query)
    {
        return $query->where('status', 'retired');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeSearch($query, $term)
    {
        // Security: Escape wildcards to prevent SQL Wildcard Injection (DoS)
        $escapedTerm = str_replace(['%', '_'], ['\%', '\_'], $term);

        return $query->where(function ($q) use ($escapedTerm) {
            $q->where('name', 'like', "%{$escapedTerm}%")
                ->orWhere('description', 'like', "%{$escapedTerm}%")
                ->orWhere('sku', 'like', "%{$escapedTerm}%");
        });
    }

    // Lifecycle Methods


    public function retire($reason = null)
    {
        $this->update([
            'status' => 'retired',
            'retired_at' => now(),
            'retirement_reason' => $reason,
        ]);
    }

    public function activate()
    {
        $this->update([
            'status' => 'active',
            'retired_at' => null,
            'retirement_reason' => null,
        ]);
    }

    public function approve()
    {
        $this->update(['status' => 'active']);
    }

    public function reject()
    {
        $this->delete();
    }

    // Relationships
    public function category()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function unitType()
    {
        return $this->belongsTo(UnitType::class, 'unit_type_id');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('display_order')->latest('id');
    }

    public function options()
    {
        return $this->hasMany(ProductOption::class);
    }

    public function suggestedBy()
    {
        return $this->belongsTo(User::class, 'suggested_by');
    }

    // Accessors
    public function getPrimaryImageUrlAttribute()
    {
        if ($this->relationLoaded('images')) {
            if ($this->images->isEmpty()) return null;
            $imagePath = $this->images->first()->image_path;
        } else {
            // Avoid N+1: Use a direct query if relation is not loaded
            $firstImage = $this->images()->orderBy('display_order')->first();
            if (!$firstImage) return null;
            $imagePath = $firstImage->image_path;
        }

        if (\Illuminate\Support\Str::startsWith($imagePath, ['http://', 'https://'])) {
            return $imagePath;
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        return $disk->url($imagePath);
    }

    public function getCurrencySymbolAttribute()
    {
        return Setting::getCurrencySymbol();
    }
}
