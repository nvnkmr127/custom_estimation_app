<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomTemplate extends Model
{
    protected $fillable = ['name', 'description', 'items', 'allowed_unit_types'];

    protected $casts = [
        'items' => 'array',
        'allowed_unit_types' => 'array',
    ];
}
