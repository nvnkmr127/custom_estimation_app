<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomationStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'automation_id',
        'order',
        'delay',
        'retry_limit',
        'on_failure',
        'condition_logic',
    ];

    public function automation()
    {
        return $this->belongsTo(Automation::class);
    }

    public function conditions()
    {
        return $this->hasMany(AutomationCondition::class);
    }

    public function action()
    {
        return $this->hasOne(AutomationAction::class);
    }
}
