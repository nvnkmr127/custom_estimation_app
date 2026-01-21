<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationSchedule extends Model
{
    protected $fillable = [
        'automation_id',
        'name',
        'cron_expression',
        'timezone',
        'next_run_at',
        'last_run_at',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the automation that owns the schedule.
     */
    public function automation()
    {
        return $this->belongsTo(Automation::class);
    }
}
