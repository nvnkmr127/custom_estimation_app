<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomationTrigger extends Model
{
    use HasFactory;

    protected $fillable = [
        'automation_id',
        'event_name',
    ];

    public function automation()
    {
        return $this->belongsTo(Automation::class);
    }
}
