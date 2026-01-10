<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Follower extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'followable_type',
        'followable_id',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function followable()
    {
        return $this->morphTo();
    }

    /**
     * Check if follower has a specific permission
     */
    public function hasPermission($permission)
    {
        if (empty($this->permissions)) {
            return false;
        }
        return in_array($permission, $this->permissions);
    }
}
