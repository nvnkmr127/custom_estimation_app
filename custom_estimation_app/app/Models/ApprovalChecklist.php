<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalChecklist extends Model
{
    use HasFactory;

    protected $fillable = ['task', 'is_required'];

    protected static function booted()
    {
        static::updated(fn () => \Illuminate\Support\Facades\Cache::forget('approval_checklists'));
        static::created(fn () => \Illuminate\Support\Facades\Cache::forget('approval_checklists'));
        static::deleted(fn () => \Illuminate\Support\Facades\Cache::forget('approval_checklists'));
    }

    public static function getAllCached()
    {
        return \Illuminate\Support\Facades\Cache::remember('approval_checklists', 86400, function () {
            return static::all();
        });
    }
}
