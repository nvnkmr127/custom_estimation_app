<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BackupLog extends Model
{
    protected $fillable = [
        'filename',
        'path',
        'size_bytes',
        'disk',
        'status',          // pending | completed | failed | uploading_drive | drive_uploaded | restoring
        'triggered_by',    // system | manual | schedule | consolidate
        'backup_type',     // checkpoint | final
        'is_incremental',
        'parent_id',
        'google_drive_id',
        'google_drive_url',
        'manifest',
        'hash',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'size_bytes' => 'integer',
        'is_incremental' => 'boolean',
        'manifest' => 'array',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function parent(): BelongsTo
    {
        return $this->belongsTo(BackupLog::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(BackupLog::class, 'parent_id');
    }

    // -------------------------------------------------------------------------
    // Type constants
    // -------------------------------------------------------------------------

    const TYPE_CHECKPOINT = 'checkpoint';
    const TYPE_FINAL = 'final';

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size_bytes ?? 0;
        if ($bytes < 1024)
            return "{$bytes} B";
        if ($bytes < 1048576)
            return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1073741824)
            return round($bytes / 1048576, 2) . ' MB';
        return round($bytes / 1073741824, 2) . ' GB';
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'completed' => ['color' => 'green', 'label' => 'Local Backup Done'],
            'drive_uploaded' => ['color' => 'blue', 'label' => 'Drive Uploaded'],
            'uploading_drive' => ['color' => 'yellow', 'label' => 'Uploading to Drive…'],
            'restoring' => ['color' => 'orange', 'label' => 'Restoring…'],
            'failed' => ['color' => 'red', 'label' => 'Failed'],
            'pending' => ['color' => 'gray', 'label' => 'Pending'],
            default => ['color' => 'gray', 'label' => ucfirst($this->status)],
        };
    }

    public function isCheckpoint(): bool
    {
        return $this->backup_type === self::TYPE_CHECKPOINT;
    }

    public function isFinal(): bool
    {
        return $this->backup_type === self::TYPE_FINAL;
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['completed', 'drive_uploaded']);
    }

    public function scopeCheckpoints($query)
    {
        return $query->where('backup_type', self::TYPE_CHECKPOINT);
    }

    public function scopeFinals($query)
    {
        return $query->where('backup_type', self::TYPE_FINAL);
    }

    public function scopeTodaysCheckpoints($query)
    {
        return $query->checkpoints()->whereDate('created_at', today());
    }
}
