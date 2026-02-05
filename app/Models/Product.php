<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%");
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
        if ($this->images->isEmpty()) {
            return null;
        }

        $imagePath = $this->images->first()->image_path;

        if (\Illuminate\Support\Str::startsWith($imagePath, ['http://', 'https://'])) {
            return $imagePath;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($imagePath);
    }
}
