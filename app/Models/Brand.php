<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'logo_url',
        'primary_color',
        'secondary_color',
        'footer_text',
        'support_email',
        'website_url',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function ($brand) {
            if ($brand->is_default) {
                // Ensure only one default brand exists
                static::where('id', '!=', $brand->id)->update(['is_default' => false]);
            }
        });
    }
}
