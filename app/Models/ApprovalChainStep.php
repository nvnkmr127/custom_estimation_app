<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalChainStep extends Model
{
    protected $fillable = [
        'approval_chain_id',
        'role',
        'user_id',
        'order',
        'require_all',
        'timeout_hours',
        'timeout_action',
    ];

    public function chain()
    {
        return $this->belongsTo(ApprovalChain::class, 'approval_chain_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
