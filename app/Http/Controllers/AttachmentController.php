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
        $organization = $request->user()->organization;

        // Handle link attachments
        if ($request->has('url') && $request->url) {
            $request->validate([
                'url' => 'required|url|max:2048',
                'name' => 'required|string|max:255',
                'attachable_type' => 'required|string',
                'attachable_id' => 'required|uuid',
            ]);

            $attachment = Attachment::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'organization_id' => $organization->id,
                'attachable_type' => $request->attachable_type,
                'attachable_id' => $request->attachable_id,
                'name' => $request->name,
                'url' => $request->url,
                'mime_type' => 'application/link',
                'uploaded_by_id' => Auth::id(),
            ]);

            // Also create a Document record for better organization
            if ($request->category) {
                \App\Models\Document::create([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'organization_id' => $organization->id,
                    'name' => $request->name,
                    'description' => "Link: {$request->url}",
                    'category' => $request->category,
                    'type' => 'link',
                    'status' => 'active',
                    'created_by_id' => Auth::id(),
                ]);
            }

            return response()->json([
                'success' => true,
                'attachment' => $attachment->load('uploadedBy'),
            ]);
        }

        // Handle file attachments
        $request->validate([
            'file' => 'required|file|mimes:jpeg,jpg,png,gif,pdf,doc,docx,xls,xlsx,txt|max:10240', // 10MB max
            'attachable_type' => 'required|string', // e.g., 'App\Models\Customer'
            'attachable_id' => 'required|uuid',
            'category' => 'nullable|string',
        ]);

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

        // If it's a link, redirect to the URL
        if ($attachment->url) {
            return redirect($attachment->url);
        }

        if (!$attachment->file_path || !Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'File not found');
        }

        return response()->download(
            Storage::disk('public')->path($attachment->file_path),
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

