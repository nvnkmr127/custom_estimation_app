<?php

namespace App\Services;

use App\Models\BackupLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Drive Backup Service
 *
 * Uses Google OAuth2 with a refresh token.
 * Required .env keys:
 *   GOOGLE_DRIVE_CLIENT_ID
 *   GOOGLE_DRIVE_CLIENT_SECRET
 *   GOOGLE_DRIVE_REFRESH_TOKEN
 *   GOOGLE_DRIVE_FOLDER_ID   (optional — uploads to Drive root if unset)
 *
 * Obtain a refresh token via https://developers.google.com/oauthplayground/
 * Scope: https://www.googleapis.com/auth/drive.file
 */
class GoogleDriveBackupService
{
    protected string $tokenUrl = 'https://oauth2.googleapis.com/token';
    protected string $filesUrl = 'https://www.googleapis.com/drive/v3/files';
    protected string $uploadBase = 'https://www.googleapis.com/upload/drive/v3/files';

    protected ?string $accessToken = null;

    // -------------------------------------------------------------------------
    // Configuration helpers
    // -------------------------------------------------------------------------

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

    // -------------------------------------------------------------------------
    // OAuth Access Token
    // -------------------------------------------------------------------------

    protected function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $response = Http::asForm()->post($this->tokenUrl, [
            'client_id' => config('services.google_drive.client_id'),
            'client_secret' => config('services.google_drive.client_secret'),
            'refresh_token' => config('services.google_drive.refresh_token'),
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to obtain Google Drive access token: ' . $response->body());
        }

        $this->accessToken = $response->json('access_token');
        return $this->accessToken;
    }

    // -------------------------------------------------------------------------
    // Upload — uses multipart/related (required by Drive API)
    // -------------------------------------------------------------------------

    /**
     * Upload a backup ZIP to Google Drive.
     * Returns ['id' => ..., 'url' => ...] on success.
     *
     * IMPORTANT: Google Drive multipart upload requires Content-Type:
     * multipart/related — NOT multipart/form-data.
     * We build the raw body manually.
     */
    public function upload(BackupLog $log): array
    {
        $token = $this->getAccessToken();
        $localPath = storage_path('app/private/' . $log->path);

        if (!file_exists($localPath)) {
            throw new \RuntimeException("Backup file not found: {$localPath}");
        }

        $folderId = config('services.google_drive.folder_id');

        // JSON metadata part
        $metadata = ['name' => $log->filename, 'mimeType' => 'application/zip'];
        if ($folderId) {
            $metadata['parents'] = [$folderId];
        }
        $metaJson = json_encode($metadata);

        // Build multipart/related body
        $boundary = 'BackupBoundary' . uniqid();
        $fileData = file_get_contents($localPath);

        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
        $body .= $metaJson . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: application/zip\r\n\r\n";
        $body .= $fileData . "\r\n";
        $body .= "--{$boundary}--";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'multipart/related; boundary="' . $boundary . '"',
            'Content-Length' => strlen($body),
        ])->withBody($body, 'multipart/related')
            ->post($this->uploadBase . '?uploadType=multipart&fields=id,webViewLink');

        if ($response->failed()) {
            Log::error('[GoogleDriveBackupService] Upload failed: ' . $response->body());
            throw new \RuntimeException('Google Drive upload failed: ' . $response->body());
        }

        return [
            'id' => $response->json('id'),
            'url' => $response->json('webViewLink'),
        ];
    }

    // -------------------------------------------------------------------------
    // List files in the configured backup folder
    // -------------------------------------------------------------------------

    public function listFiles(int $limit = 20): array
    {
        $token = $this->getAccessToken();
        $folderId = config('services.google_drive.folder_id');

        $query = "mimeType='application/zip' and trashed=false";
        if ($folderId) {
            $query .= " and '{$folderId}' in parents";
        }

        $response = Http::withToken($token)->get($this->filesUrl, [
            'q' => $query,
            'orderBy' => 'createdTime desc',
            'pageSize' => $limit,
            'fields' => 'files(id,name,size,createdTime,webViewLink)',
        ]);

        if ($response->failed()) {
            Log::error('[GoogleDriveBackupService] listFiles failed: ' . $response->body());
            return [];
        }

        return $response->json('files', []);
    }

    // -------------------------------------------------------------------------
    // Delete a file from Drive
    // -------------------------------------------------------------------------

    public function deleteFile(string $fileId): bool
    {
        $token = $this->getAccessToken();
        $response = Http::withToken($token)->delete("{$this->filesUrl}/{$fileId}");
        return $response->successful();
    }

    // -------------------------------------------------------------------------
    // Test connection
    // -------------------------------------------------------------------------

    public function testConnection(): array
    {
        try {
            if (!$this->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'Google Drive credentials are not configured. Add GOOGLE_DRIVE_CLIENT_ID, GOOGLE_DRIVE_CLIENT_SECRET, and GOOGLE_DRIVE_REFRESH_TOKEN to your .env file.',
                ];
            }

            $token = $this->getAccessToken();

            // List 1 file — enough to verify auth + scope
            $response = Http::withToken($token)->get($this->filesUrl, [
                'pageSize' => 1,
                'fields' => 'files(id,name)',
            ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Google Drive connected successfully!'];
            }

            return ['success' => false, 'message' => 'Connection failed: ' . $response->body()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
