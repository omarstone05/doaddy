<?php

namespace App\Services\Document;

use App\Models\Attachment;
use App\Models\Document;
use App\Models\AddyChatMessage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentStorageService
{
    /**
     * Store a document and link it to an entity
     */
    public function storeDocument(
        UploadedFile $file,
        string $organizationId,
        ?string $attachableType = null,
        ?string $attachableId = null,
        ?string $category = null,
        ?int $uploadedById = null
    ): Attachment {
        // Store the file
        $filePath = $file->store("documents/{$organizationId}/" . date('Y/m'), 'public');
        $fileName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();

        // Create attachment record
        $attachment = Attachment::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $organizationId,
            'attachable_type' => $attachableType,
            'attachable_id' => $attachableId,
            'name' => $fileName,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'uploaded_by_id' => $uploadedById,
        ]);

        // If category is provided, also create a Document record for better organization
        if ($category) {
            Document::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organizationId,
                'name' => $fileName,
                'description' => "Document uploaded via attachment",
                'category' => $category,
                'type' => $this->getDocumentType($mimeType),
                'status' => 'active',
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'created_by_id' => $uploadedById,
            ]);
        }

        return $attachment;
    }

    /**
     * Store document from chat message
     */
    public function storeFromChat(
        array $fileData,
        string $organizationId,
        int $chatMessageId,
        ?int $uploadedById = null
    ): Attachment {
        // File is already stored, just create attachment record
        $attachment = Attachment::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $organizationId,
            'attachable_type' => AddyChatMessage::class,
            'attachable_id' => $chatMessageId,
            'name' => $fileData['file_name'],
            'file_path' => $fileData['file_path'],
            'file_name' => $fileData['file_name'],
            'file_size' => $fileData['file_size'],
            'mime_type' => $fileData['mime_type'],
            'uploaded_by_id' => $uploadedById,
        ]);

        return $attachment;
    }

    /**
     * Get document type from MIME type
     */
    protected function getDocumentType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }
        if ($mimeType === 'application/pdf') {
            return 'pdf';
        }
        if (str_contains($mimeType, 'wordprocessingml') || str_contains($mimeType, 'msword')) {
            return 'word';
        }
        if (str_contains($mimeType, 'spreadsheetml') || str_contains($mimeType, 'ms-excel')) {
            return 'excel';
        }
        if (str_starts_with($mimeType, 'text/')) {
            return 'text';
        }
        return 'other';
    }

    /**
     * Delete document and file
     */
    public function deleteDocument(Attachment $attachment): bool
    {
        // Delete file from storage
        if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        // Delete attachment record
        return $attachment->delete();
    }
}

