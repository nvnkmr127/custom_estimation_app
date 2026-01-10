<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'event_name',
        'event_class',
        'entity_type',
        'entity_id',
        'triggered_by',
        'source',
        'payload',
        'status',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($eventLog) {
            throw new \Exception('Event logs are immutable and cannot be deleted.');
        });

        static::updating(function ($eventLog) {
            throw new \Exception('Event logs are immutable and cannot be updated.');
        });
    }
    public function listeners()
    {
        return $this->hasMany(EventListenerLog::class, 'event_id', 'event_id');
    }
}
