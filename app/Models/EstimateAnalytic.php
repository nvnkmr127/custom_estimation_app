<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstimateAnalytic extends Model
{
    use HasFactory;

    protected $fillable = [
        'estimate_id',
        'action',
        'ip_address',
        'user_agent',
        'device',
        'browser',
        'platform',
        'location_json',
        'is_unique',
    ];

    protected $casts = [
        'is_unique' => 'boolean',
        'location_json' => 'array',
    ];

    public function estimate()
    {
        return $this->belongsTo(Estimate::class);
    }
}
