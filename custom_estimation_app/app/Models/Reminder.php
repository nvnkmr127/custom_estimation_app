<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'remindable_type',
        'remindable_id',
        'remind_at',
        'is_sent',
        'sent_at',
        'type',
    ];

    protected $casts = [
        'remind_at' => 'datetime',
        'sent_at' => 'datetime',
        'is_sent' => 'boolean',
    ];

    // Scopes
    public function scopePending($query)
    {
        return $query->where('is_sent', false);
    }

    public function scopeDue($query)
    {
        return $query->where('is_sent', false)
            ->where('remind_at', '<=', now());
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeUpcoming($query, $days = 7)
    {
        return $query->where('is_sent', false)
            ->where('remind_at', '>=', now())
            ->where('remind_at', '<=', now()->addDays($days));
    }

    // Methods
    public function markAsSent()
    {
        $this->update([
            'is_sent' => true,
            'sent_at' => now(),
        ]);
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function remindable()
    {
        return $this->morphTo();
    }
}
