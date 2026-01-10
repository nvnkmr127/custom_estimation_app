<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitType extends Model
{
    protected $fillable = ['name', 'units'];

    protected $casts = [
        'units' => 'array',
    ];
}
