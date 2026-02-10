<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShortUrl extends Model
{
    protected $fillable = [
        'short_code',
        'long_url',
        'expires_at',
        'clicks',
        'last_accessed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'clicks' => 'integer',
    ];

    /**
     * Check if the short URL has expired
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Get the full short URL
     *
     * @return string
     */
    public function getShortUrlAttribute(): string
    {
        return url("/s/{$this->short_code}");
    }
}
