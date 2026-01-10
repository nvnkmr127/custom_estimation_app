<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingNotification extends Model
{
    protected $fillable = [
        'user_id',
        'event_type',
        'event_id',
        'template',
        'payload',
        'frequency',
        'sent_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnsent($query)
    {
        return $query->whereNull('sent_at');
    }
}
