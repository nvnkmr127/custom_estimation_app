<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PluginModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'plugin_id',
        'name',
        'key',
        'is_active',
        'type', // 'outbound', 'inbound', 'sync'
        'event_name',
        'uuid',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($module) {
            if (empty($module->uuid)) {
                $module->uuid = (string) Str::uuid();
            }
        });
    }

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PluginModuleLog::class);
    }

    public function getCatchUrlAttribute(): string
    {
        return route('admin.plugins.catch', ['uuid' => $this->uuid]);
    }
}
