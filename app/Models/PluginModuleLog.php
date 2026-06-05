<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PluginModuleLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'plugin_module_id',
        'direction', // 'inbound', 'outbound'
        'status', // 'success', 'failed'
        'payload',
        'headers',
        'response_code',
        'response_body',
        'latency_ms',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
        'response_code' => 'integer',
        'latency_ms' => 'integer',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(PluginModule::class, 'plugin_module_id');
    }
}
