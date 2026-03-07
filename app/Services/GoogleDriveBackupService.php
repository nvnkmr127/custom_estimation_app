<?php

namespace App\Services;

use App\Models\BackupLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Google Drive Backup Service
 *
 * Handles resumable uploads and 3-month rotation strategy.
 */
class GoogleDriveBackupService
{
    protected string $tokenUrl = 'https://oauth2.googleapis.com/token';
    protected string $filesUrl = 'https://www.googleapis.com/drive/v3/files';
    protected string $uploadBase = 'https://www.googleapis.com/upload/drive/v3/files';
    protected int $chunkSize = 5 * 1024 * 1024;
    protected ?string $accessToken = null;

    public function isConfigured(): bool
    {
        return !empty(config('services.google_drive.client_id'))
            && !empty(config('services.google_drive.client_secret'))
            && !empty(config('services.google_drive.refresh_token'));
    }

    public function isEnabled(): bool
    {
        return (Setting::where('key', 'backup_google_drive_enabled')->value('value') ?? '0') === '1';
    }

    protected function getAccessToken(): string
    {
        if ($this->accessToken)
            return $this->accessToken;

        $response = Http::asForm()->post($this->tokenUrl, [
            'client_id' => config('services.google_drive.client_id'),
            'client_secret' => config('services.google_drive.client_secret'),
            'refresh_token' => config('services.google_drive.refresh_token'),
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed())
            throw new \RuntimeException('Auth failed: ' . $response->body());

        return $this->accessToken = $response->json('access_token');
    }

    /**
     * Get or create a subfolder within the main backup folder.
     */
    protected function getOrCreateSubfolder(string $name): string
    {
        $token = $this->getAccessToken();
        $parentFolderId = config('services.google_drive.folder_id');

        // Search if folder exists
        $query = "name = '" . str_replace("'", "\\'", $name) . "' and mimeType = 'application/vnd.google-apps.folder' and trashed = false";
        if ($parentFolderId) {
            $query .= " and '{$parentFolderId}' in parents";
        }

        $search = Http::withToken($token)->get($this->filesUrl, [
            'q' => $query,
            'fields' => 'files(id)',
        ]);

        $files = $search->json('files', []);
        if (!empty($files)) {
            return $files[0]['id'];
        }

        // Create it
        $metadata = [
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
        ];
        if ($parentFolderId) {
            $metadata['parents'] = [$parentFolderId];
        }

        $create = Http::withToken($token)->post($this->filesUrl, $metadata);

        if ($create->failed()) {
            throw new \RuntimeException("Failed to create folder '{$name}': " . $create->body());
        }

        return $create->json('id');
    }

    public function upload(BackupLog $log): array
    {
        $token = $this->getAccessToken();
        $localPath = storage_path('app/private/' . $log->path);
        if (!file_exists($localPath))
            throw new \RuntimeException("File missing: {$localPath}");

        $fileSize = filesize($localPath);

        // Determine subfolder based on filename prefix or log properties
        $subfolderName = 'Database Backups';
        if (str_starts_with($log->filename, 'full_')) {
            $subfolderName = 'Full Code Backups';
        } elseif ($log->is_incremental || str_starts_with($log->filename, 'inc_')) {
            $subfolderName = 'Incremental Backups';
        } elseif (str_starts_with($log->filename, 'checkpoint_')) {
            $subfolderName = 'Checkpoints';
        }

        $folderId = $this->getOrCreateSubfolder($subfolderName);

        $metadata = ['name' => $log->filename, 'mimeType' => 'application/zip'];
        if ($folderId)
            $metadata['parents'] = [$folderId];

        $init = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Upload-Content-Type' => 'application/zip',
            'X-Upload-Content-Length' => (string) $fileSize,
        ])->post($this->uploadBase . '?uploadType=resumable&fields=id,webViewLink', $metadata);

        $uploadUrl = $init->header('Location');
        if (!$uploadUrl)
            throw new \RuntimeException('No session URL');

        $handle = fopen($localPath, 'rb');
        $offset = 0;
        $result = null;

        try {
            while (!feof($handle)) {
                $chunk = fread($handle, $this->chunkSize);
                $len = strlen($chunk);
                $end = $offset + $len - 1;

                $ch = curl_init($uploadUrl);
                curl_setopt_array($ch, [
                    CURLOPT_CUSTOMREQUEST => 'PUT',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POSTFIELDS => $chunk,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $token,
                        'Content-Type: application/octet-stream',
                        "Content-Range: bytes $offset-$end/$fileSize",
                        "Content-Length: $len",
                    ],
                ]);
                $body = curl_exec($ch);
                $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($status === 200 || $status === 201) {
                    $result = json_decode($body, true);
                    break;
                }
                $offset += $len;
            }
        } finally {
            fclose($handle);
        }

        return ['id' => $result['id'], 'url' => $result['webViewLink']];
    }

    /**
     * Delete backups older than 90 days (3 months) looking in all subfolders.
     */
    public function rotate(): int
    {
        $files = $this->listFiles(500); // Higher limit to see all types
        if (empty($files))
            return 0;

        $deleted = 0;
        foreach ($files as $file) {
            $created = Carbon::parse($file['createdTime']);
            $ageDays = $created->diffInDays(now());

            if ($ageDays > 90) {
                if ($this->deleteFile($file['id'])) {
                    $deleted++;
                    Log::info("[GoogleDriveBackupService] Purged: " . $file['name']);
                }
            }
        }
        return $deleted;
    }

    public function listFiles(int $limit = 20): array
    {
        $token = $this->getAccessToken();
        $parentFolderId = config('services.google_drive.folder_id');

        // Find zip files. If parent is specified, we might want to search recursively.
        // Google Drive API 'q' supports finding files by parent, but searching the whole tree
        // is easier by omitting 'in parents' if we trust our naming convention or just listing all zips
        // that our app owns.

        $query = "mimeType='application/zip' and trashed=false";

        // If we want to stay within the app's root folder only:
        // We'd have to find all subfolders first or use 'root' if configured that way.
        // For simplicity, we search for all Zips.

        $response = Http::withToken($token)->get($this->filesUrl, [
            'q' => $query,
            'orderBy' => 'createdTime desc',
            'pageSize' => $limit,
            'fields' => 'files(id,name,size,createdTime,webViewLink)',
        ]);
        return $response->json('files', []);
    }

    public function deleteFile(string $fileId): bool
    {
        return Http::withToken($this->getAccessToken())->delete("{$this->filesUrl}/{$fileId}")->successful();
    }

    public function testConnection(): array
    {
        try {
            $response = Http::withToken($this->getAccessToken())->get($this->filesUrl, ['pageSize' => 1]);
            return ['success' => $response->successful(), 'message' => $response->successful() ? 'Connected!' : $response->body()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
