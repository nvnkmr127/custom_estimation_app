<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'subject',
        'body_html',
        'design_json',
        'description',
        'variables',
    ];

    protected $casts = [
        'variables' => 'array',
        'design_json' => 'array',
    ];
}
