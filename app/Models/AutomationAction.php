<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomationAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'automation_step_id',
        'type',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function step()
    {
        return $this->belongsTo(AutomationStep::class, 'automation_step_id');
    }
}
