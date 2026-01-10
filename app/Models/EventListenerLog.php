<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventListenerLog extends Model
{
    protected $fillable = [
        'event_id',
        'listener_class',
        'status',
        'attempts',
        'error_message',
    ];

    public function eventLog()
    {
        return $this->belongsTo(EventLog::class, 'event_id', 'event_id');
    }
}
