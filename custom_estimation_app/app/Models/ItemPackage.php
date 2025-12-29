<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemPackage extends Model
{
    protected $fillable = ['name', 'description', 'total_price', 'items'];

    protected $casts = [
        'items' => 'array',
        'total_price' => 'decimal:2',
    ];
}
