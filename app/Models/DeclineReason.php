<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeclineReason extends Model
{
    use HasFactory;

    protected $fillable = [
        'reason',
        'is_active',
    ];

    protected static function booted()
    {
        static::updated(fn () => \Illuminate\Support\Facades\Cache::forget('active_decline_reasons'));
        static::created(fn () => \Illuminate\Support\Facades\Cache::forget('active_decline_reasons'));
        static::deleted(fn () => \Illuminate\Support\Facades\Cache::forget('active_decline_reasons'));
    }

    public static function getActiveCached()
    {
        return \Illuminate\Support\Facades\Cache::remember('active_decline_reasons', 86400, function () {
            return static::where('is_active', true)->get();
        });
    }
}
