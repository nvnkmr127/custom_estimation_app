<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookSubscription extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'name',
        'target_url',
        'secret_key',
        'subscribed_events',
        'headers',
        'is_active',
        'status',
    ];

    protected $casts = [
        'subscribed_events' => 'array',
        'headers' => 'array',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WebhookOutboundLog::class, 'subscription_id');
    }
}
