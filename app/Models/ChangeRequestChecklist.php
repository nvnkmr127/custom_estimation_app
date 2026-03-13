<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChangeRequestChecklist extends Model
{
    use HasFactory;

    protected $fillable = ['task', 'display_order'];

    protected static function booted()
    {
        static::updated(fn () => \Illuminate\Support\Facades\Cache::forget('change_request_checklists'));
        static::created(fn () => \Illuminate\Support\Facades\Cache::forget('change_request_checklists'));
        static::deleted(fn () => \Illuminate\Support\Facades\Cache::forget('change_request_checklists'));
    }

    public static function getAllCached()
    {
        return \Illuminate\Support\Facades\Cache::remember('change_request_checklists', 86400, function () {
            return static::orderBy('display_order')->get();
        });
    }
}
