<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomationCondition extends Model
{
    use HasFactory;

    protected $fillable = [
        'automation_id',
        'automation_step_id',
        'type',
        'field',
        'operator',
        'value',
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
