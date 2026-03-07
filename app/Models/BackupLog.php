<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupLog extends Model
{
    protected $fillable = [
        'filename',
        'path',
        'size_bytes',
        'disk',
        'status',          // pending | completed | failed | uploading_drive | drive_uploaded
        'triggered_by',    // system | manual | schedule
        'google_drive_id',
        'google_drive_url',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'size_bytes' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size_bytes ?? 0;
        if ($bytes < 1024) {
            return "{$bytes} B";
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        } elseif ($bytes < 1073741824) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        return round($bytes / 1073741824, 2) . ' GB';
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'completed' => ['color' => 'green', 'label' => 'Local Backup Done'],
            'drive_uploaded' => ['color' => 'blue', 'label' => 'Drive Uploaded'],
            'uploading_drive' => ['color' => 'yellow', 'label' => 'Uploading to Drive…'],
            'failed' => ['color' => 'red', 'label' => 'Failed'],
            'pending' => ['color' => 'gray', 'label' => 'Pending'],
            default => ['color' => 'gray', 'label' => ucfirst($this->status)],
        };
    }

    // Convenience scope
    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['completed', 'drive_uploaded']);
    }
}
