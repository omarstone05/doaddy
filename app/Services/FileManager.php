<?php

namespace App\Services;

use App\Models\UploadedFile;
use App\Models\User;
use Illuminate\Http\UploadedFile as HttpUploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileManager
{
    protected GoogleDriveService $driveService;
    protected bool $useGoogleDrive;

    public function __construct(GoogleDriveService $driveService)
    {
        $this->driveService = $driveService;
        $this->useGoogleDrive = config('services.google.drive_folder_id') && $this->driveService->isAuthenticated();
    }

    /**
     * Upload file (to Google Drive or local storage)
     */
    public function upload(HttpUploadedFile $file, User $user, string $fileType = 'document', ?string $organizationId = null): UploadedFile
    {
        $organizationId = $organizationId ?? session('current_organization_id') ?? $user->current_organization_id;
        $organization = \App\Models\Organization::find($organizationId);

        if ($this->useGoogleDrive && $organization) {
            return $this->uploadToGoogleDrive($file, $user, $fileType, $organization);
        }

        return $this->uploadToLocal($file, $user, $fileType, $organizationId);
    }

    /**
     * Upload to Google Drive
     */
    protected function uploadToGoogleDrive(HttpUploadedFile $file, User $user, string $fileType, \App\Models\Organization $organization): UploadedFile
    {
        // Get or create organization folder
        $orgFolderId = $this->driveService->getOrCreateOrganizationFolder(
            $organization->id,
            $organization->name
        );

        // Get or create type folder (receipts, invoices, etc.)
        $typeFolderId = $this->getOrCreateTypeFolder($orgFolderId, $fileType);

        // Upload file
        $tempPath = $file->getRealPath();
        $fileName = $file->getClientOriginalName();
        $driveFileId = $this->driveService->uploadFile($tempPath, $fileName, $typeFolderId);

        if (!$driveFileId) {
            // Fallback to local storage
            return $this->uploadToLocal($file, $user, $fileType, $organization->id);
        }

        // Create database record
        return UploadedFile::create([
            'id' => Str::uuid(),
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'original_name' => $fileName,
            'file_name' => $fileName,
            'file_type' => $fileType,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'storage_driver' => 'google',
            'storage_path' => $driveFileId,
            'metadata' => [
                'folder_id' => $typeFolderId,
                'organization_folder_id' => $orgFolderId,
            ],
        ]);
    }

    /**
     * Upload to local storage
     */
    protected function uploadToLocal(HttpUploadedFile $file, User $user, string $fileType, string $organizationId): UploadedFile
    {
        $path = $file->store("uploads/{$organizationId}/{$fileType}", 'public');

        return UploadedFile::create([
            'id' => Str::uuid(),
            'organization_id' => $organizationId,
            'user_id' => $user->id,
            'original_name' => $file->getClientOriginalName(),
            'file_name' => basename($path),
            'file_type' => $fileType,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'storage_driver' => 'local',
            'storage_path' => $path,
        ]);
    }

    /**
     * Download file (from Google Drive or local storage)
     */
    public function download(UploadedFile $uploadedFile): string
    {
        if ($uploadedFile->storage_driver === 'google') {
            $tempPath = $this->driveService->downloadFile($uploadedFile->storage_path);
            if ($tempPath) {
                return $tempPath;
            }
            throw new \Exception('Failed to download file from Google Drive');
        }

        return Storage::disk('public')->path($uploadedFile->storage_path);
    }

    /**
     * Delete file
     */
    public function delete(UploadedFile $uploadedFile): bool
    {
        if ($uploadedFile->storage_driver === 'google') {
            $deleted = $this->driveService->deleteFile($uploadedFile->storage_path);
            if ($deleted) {
                $uploadedFile->delete();
                return true;
            }
            return false;
        }

        Storage::disk('public')->delete($uploadedFile->storage_path);
        $uploadedFile->delete();
        return true;
    }

    /**
     * Get storage statistics
     */
    public function getStorageStats(string $organizationId): array
    {
        $files = UploadedFile::where('organization_id', $organizationId)->get();

        $totalSize = $files->sum('file_size');
        $byType = [];

        foreach ($files as $file) {
            $type = $file->file_type ?? 'other';
            if (!isset($byType[$type])) {
                $byType[$type] = ['count' => 0, 'size' => 0];
            }
            $byType[$type]['count']++;
            $byType[$type]['size'] += $file->file_size;
        }

        // Format sizes
        foreach ($byType as $type => &$data) {
            $data['human_size'] = $this->formatBytes($data['size']);
        }

        return [
            'file_count' => $files->count(),
            'total_size' => $totalSize,
            'total_size_human' => $this->formatBytes($totalSize),
            'by_type' => $byType,
        ];
    }

    /**
     * Get or create type folder (receipts, invoices, etc.)
     */
    protected function getOrCreateTypeFolder(string $parentFolderId, string $fileType): string
    {
        $cacheKey = "google_drive_type_folder_{$parentFolderId}_{$fileType}";
        $cachedFolderId = cache()->get($cacheKey);

        if ($cachedFolderId) {
            return $cachedFolderId;
        }

        // Try to find existing folder
        try {
            $reflection = new \ReflectionClass($this->driveService);
            $driveProperty = $reflection->getProperty('drive');
            $driveProperty->setAccessible(true);
            $drive = $driveProperty->getValue($this->driveService);
            
            $query = "name='{$fileType}' and mimeType='application/vnd.google-apps.folder' and '{$parentFolderId}' in parents and trashed=false";
            $results = $drive->files->listFiles([
                'q' => $query,
                'fields' => 'files(id, name)',
            ]);

            if (count($results->getFiles()) > 0) {
                $folderId = $results->getFiles()[0]->getId();
                cache()->put($cacheKey, $folderId, now()->addDays(24));
                return $folderId;
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to search for type folder', ['error' => $e->getMessage()]);
        }

        // Create new folder
        $folderId = $this->driveService->createFolder($fileType, $parentFolderId);
        if ($folderId) {
            cache()->put($cacheKey, $folderId, now()->addDays(24));
        }

        return $folderId ?? $parentFolderId; // Fallback to parent if creation fails
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

