<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Automation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'condition_logic',
        'settings',
        'is_active',
        'parent_id',
        'version',
        'is_current_version',
        'created_by',
        'last_edited_by',
        'last_edited_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'is_current_version' => 'boolean',
        'last_edited_at' => 'datetime',
    ];

    public function triggers()
    {
        return $this->hasMany(AutomationTrigger::class, 'automation_id');
    }

    public function parent()
    {
        return $this->belongsTo(Automation::class, 'parent_id');
    }

    public function versions()
    {
        return $this->hasMany(Automation::class, 'parent_id')->orderBy('version', 'desc');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lastEditor()
    {
        return $this->belongsTo(User::class, 'last_edited_by');
    }

    public function steps()
    {
        return $this->hasMany(AutomationStep::class)->orderBy('order');
    }

    public function globalConditions()
    {
        return $this->hasMany(AutomationCondition::class)->whereNull('automation_step_id');
    }

    public function schedules()
    {
        return $this->hasMany(AutomationSchedule::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current_version', true);
    }
}
