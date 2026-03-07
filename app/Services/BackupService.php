<?php

namespace App\Services;

use App\Models\BackupLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class BackupService
{
    /**
     * The local disk used for storing backups.
     */
    protected string $disk = 'local';

    /**
     * Directory within the local disk where backups are stored.
     */
    protected string $backupDir = 'backups';

    /**
     * Run a full backup: database + storage files.
     * Returns the BackupLog model on success.
     */
    public function runBackup(string $triggeredBy = 'system'): BackupLog
    {
        $startedAt = now();
        $filename = 'backup_' . $startedAt->format('Y-m-d_His') . '_' . Str::random(6) . '.zip';
        $relativePath = $this->backupDir . '/' . $filename;

        Log::info("[BackupService] Starting backup: {$filename}");

        // Ensure the backup directory exists
        Storage::disk($this->disk)->makeDirectory($this->backupDir);

        $absolutePath = storage_path('app/private/' . $relativePath);

        $zip = new ZipArchive();
        if ($zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Cannot create ZIP archive at: {$absolutePath}");
        }

        // 1️⃣ Backup Database
        $this->addDatabaseToZip($zip);

        // 2️⃣ Backup App Storage (public uploads, settings images, etc.)
        $this->addStorageToZip($zip);

        $zip->close();

        $sizeBytes = filesize($absolutePath);

        Log::info("[BackupService] Backup complete: {$filename} ({$sizeBytes} bytes)");

        return BackupLog::create([
            'filename' => $filename,
            'path' => $relativePath,
            'size_bytes' => $sizeBytes,
            'disk' => $this->disk,
            'status' => 'completed',
            'triggered_by' => $triggeredBy,
            'google_drive_id' => null,
            'google_drive_url' => null,
            'error_message' => null,
            'started_at' => $startedAt,
            'completed_at' => now(),
        ]);
    }

    /**
     * Add database dump to the ZIP. Supports SQLite and can be extended for MySQL.
     */
    protected function addDatabaseToZip(ZipArchive $zip): void
    {
        $connection = config('database.default');

        if ($connection === 'sqlite') {
            $dbPath = config('database.connections.sqlite.database');
            if (file_exists($dbPath)) {
                $zip->addFile($dbPath, 'database/database.sqlite');
                Log::info("[BackupService] Added SQLite file: {$dbPath}");
            }
        } elseif (in_array($connection, ['mysql', 'mariadb'])) {
            $this->addMysqlDumpToZip($zip);
        } else {
            Log::warning("[BackupService] Unsupported database driver '{$connection}', skipping DB dump.");
        }
    }

    /**
     * Generate a MySQL dump and add it to the ZIP.
     */
    protected function addMysqlDumpToZip(ZipArchive $zip): void
    {
        $host = config('database.connections.mysql.host', '127.0.0.1');
        $port = config('database.connections.mysql.port', 3306);
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $tempFile = sys_get_temp_dir() . '/db_dump_' . time() . '.sql';

        $passwordArg = !empty($password) ? "-p" . escapeshellarg($password) : '';
        $cmd = sprintf(
            'mysqldump -h %s -P %d -u %s %s %s > %s 2>&1',
            escapeshellarg($host),
            (int) $port,
            escapeshellarg($username),
            $passwordArg,
            escapeshellarg($database),
            escapeshellarg($tempFile)
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode === 0 && file_exists($tempFile)) {
            $zip->addFile($tempFile, 'database/database.sql');
            Log::info("[BackupService] MySQL dump created successfully.");
        } else {
            Log::error("[BackupService] mysqldump failed (exit={$exitCode}): " . implode("\n", $output));
        }

        // Clean up temp file after ZIP is closed
        register_shutdown_function(function () use ($tempFile) {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        });
    }

    /**
     * Add the public storage directory to the ZIP.
     */
    protected function addStorageToZip(ZipArchive $zip): void
    {
        $storagePublicPath = storage_path('app/public');
        if (!is_dir($storagePublicPath)) {
            Log::info("[BackupService] No public storage dir found, skipping.");
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($storagePublicPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $addedFiles = 0;
        foreach ($files as $file) {
            if ($file->isFile()) {
                $realPath = $file->getRealPath();
                $zipEntryPath = 'storage/' . ltrim(str_replace($storagePublicPath, '', $realPath), DIRECTORY_SEPARATOR);
                $zip->addFile($realPath, $zipEntryPath);
                $addedFiles++;
            }
        }

        Log::info("[BackupService] Added {$addedFiles} files from public storage.");
    }

    /**
     * Delete a backup file from local disk.
     */
    public function deleteLocal(BackupLog $log): void
    {
        if (Storage::disk($this->disk)->exists($log->path)) {
            Storage::disk($this->disk)->delete($log->path);
        }
    }

    /**
     * Get all backup files stored locally, with size info.
     */
    public function listLocalFiles(): array
    {
        return Storage::disk($this->disk)->files($this->backupDir);
    }

    /**
     * Prune old backups, keeping only the most recent N.
     */
    public function pruneOldBackups(int $keep = 10): int
    {
        $toDelete = BackupLog::where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->skip($keep)
            ->take(9999)
            ->get();

        $deleted = 0;
        foreach ($toDelete as $log) {
            try {
                $this->deleteLocal($log);
                $log->delete();
                $deleted++;
            } catch (\Exception $e) {
                Log::warning("[BackupService] Failed to prune backup {$log->filename}: " . $e->getMessage());
            }
        }

        return $deleted;
    }
}
