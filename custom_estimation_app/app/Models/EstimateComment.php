<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EstimateComment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'estimate_id',
        'commentable_type',
        'commentable_id',
        'user_id',
        'client_name',
        'client_email',
        'comment',
        'type',
        'is_read',
        'parent_id',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * Polymorphic relationship to commentable (Item, Section, or Estimate)
     */
    public function commentable()
    {
        return $this->morphTo();
    }

    /**
     * Belongs to estimate
     */
    public function estimate()
    {
        return $this->belongsTo(Estimate::class);
    }

    /**
     * User who commented (internal staff)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Parent comment (for threaded replies)
     */
    public function parent()
    {
        return $this->belongsTo(EstimateComment::class, 'parent_id');
    }

    /**
     * Child replies
     */
    public function replies()
    {
        return $this->hasMany(EstimateComment::class, 'parent_id')->with('user');
    }

    /**
     * Scope for unread comments
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for client comments
     */
    public function scopeClientComments($query)
    {
        return $query->where('type', 'client');
    }

    /**
     * Scope for internal comments
     */
    public function scopeInternalComments($query)
    {
        return $query->where('type', 'internal');
    }

    /**
     * Get commenter name
     */
    public function getCommenterNameAttribute()
    {
        if ($this->user_id) {
            return $this->user->name;
        }

        return $this->client_name ?? 'Anonymous';
    }

    /**
     * Check if comment is from client
     */
    public function isClientComment()
    {
        return $this->type === 'client';
    }

    /**
     * Mark as read
     */
    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }
}
