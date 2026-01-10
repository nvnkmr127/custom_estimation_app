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
}
