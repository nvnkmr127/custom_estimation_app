<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'event_type',
        'channel',
        'frequency',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Common frequency constants
     */
    public const FREQUENCY_INSTANT = 'instant';
    public const FREQUENCY_DAILY = 'daily_digest';
    public const FREQUENCY_WEEKLY = 'weekly_digest';
    public const FREQUENCY_MUTED = 'muted';

    public const CHANNEL_EMAIL = 'email';
}
