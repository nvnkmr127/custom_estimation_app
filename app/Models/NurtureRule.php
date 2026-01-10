<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NurtureRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'trigger_type',
        'trigger_days',
        'condition_logic',
        'action_type',
        'action_data',
        'is_active',
    ];

    protected $casts = [
        'condition_logic' => 'array',
        'action_data' => 'array',
        'is_active' => 'boolean',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
