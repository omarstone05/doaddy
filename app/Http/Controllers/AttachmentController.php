<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Services\Document\DocumentStorageService;
use App\Services\Addy\DocumentProcessorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    protected DocumentStorageService $storageService;
    protected DocumentProcessorService $processorService;

    public function __construct()
    {
        $this->storageService = new DocumentStorageService();
        $this->processorService = new DocumentProcessorService();
    }

    /**
     * Upload attachment for an entity (Customer, Invoice, Quote, etc.)
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,jpg,png,gif,pdf,doc,docx,xls,xlsx,txt|max:10240', // 10MB max
            'attachable_type' => 'required|string', // e.g., 'App\Models\Customer'
            'attachable_id' => 'required|uuid',
            'category' => 'nullable|string',
        ]);

        $organization = $request->user()->organization;
        $file = $request->file('file');

        // Store the document
        $attachment = $this->storageService->storeDocument(
            $file,
            $organization->id,
            $request->attachable_type,
            $request->attachable_id,
            $request->category,
            Auth::id()
        );

        // Process and extract data if it's a business document
        $extractedData = null;
        try {
            $processed = $this->processorService->processFile($file, $organization->id);
            $extractedData = $processed['extracted_data'] ?? null;
        } catch (\Exception $e) {
            \Log::error('Document processing failed during upload', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'success' => true,
            'attachment' => $attachment->load('uploadedBy'),
            'extracted_data' => $extractedData,
        ]);
    }

    /**
     * Delete attachment
     */
    public function destroy($id)
    {
        $attachment = Attachment::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $this->storageService->deleteDocument($attachment);

        return response()->json(['success' => true]);
    }

    /**
     * Download attachment
     */
    public function download($id)
    {
        $attachment = Attachment::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'File not found');
        }

        return Storage::disk('public')->download(
            $attachment->file_path,
            $attachment->file_name
        );
    }

    /**
     * Get attachments for an entity
     */
    public function index(Request $request)
    {
        $request->validate([
            'attachable_type' => 'required|string',
            'attachable_id' => 'required|uuid',
        ]);

        $attachments = Attachment::where('organization_id', Auth::user()->organization_id)
            ->where('attachable_type', $request->attachable_type)
            ->where('attachable_id', $request->attachable_id)
            ->with('uploadedBy')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($attachments);
    }
}

