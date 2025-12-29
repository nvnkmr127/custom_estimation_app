<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomTemplate extends Model
{
    protected $fillable = ['name', 'description', 'items'];

    protected $casts = [
        'items' => 'array',
    ];
}
