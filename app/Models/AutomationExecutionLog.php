<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomationExecutionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'automation_id',
        'automation_step_id',
        'event_id',
        'status',
        'error',
        'payload',
        'executed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'executed_at' => 'datetime',
    ];

    public function automation()
    {
        return $this->belongsTo(Automation::class);
    }

    public function step()
    {
        return $this->belongsTo(AutomationStep::class, 'automation_step_id');
    }
}
