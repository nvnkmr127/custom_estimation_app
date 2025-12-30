<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstimateApprovalChecklistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'estimate_id',
        'approval_checklist_id',
        'is_completed',
        'completed_by',
        'completed_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function estimate()
    {
        return $this->belongsTo(Estimate::class);
    }

    public function checklist()
    {
        return $this->belongsTo(ApprovalChecklist::class, 'approval_checklist_id');
    }

    public function completer()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
