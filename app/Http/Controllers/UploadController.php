<?php

namespace App\Http\Controllers;

use App\Services\FileManager;
use App\Models\Attachment;
use App\Models\MoneyMovement;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UploadController extends Controller
{
    protected function getOrganizationId(): ?string
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        $currentOrgId = session('current_organization_id');
        if ($currentOrgId) {
            $org = $user->organizations()->where('organizations.id', $currentOrgId)->first();
            if ($org) {
                return $org->id;
            }
        }
        
        if ($user->attributes['organization_id'] ?? null) {
            $org = $user->organizations()->where('organizations.id', $user->attributes['organization_id'])->first();
            if ($org) {
                return $org->id;
            }
        }
        
        return $user->organizations()->first()?->id;
    }

    public function upload(Request $request, FileManager $fileManager)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return response()->json(['error' => 'No organization found'], 403);
        }

        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,jpg,jpeg,png,gif,webp,pdf|max:10240',
            'type' => 'required|in:csv,image',
            'context' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $type = $validated['type'];
        $context = $validated['context'] ?? 'general';

        DB::beginTransaction();
        try {
            if ($type === 'csv') {
                return $this->handleCSVUpload($file, $organizationId, $context, $fileManager);
            } else {
                return $this->handleImageUpload($file, $organizationId, $context, $fileManager);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function handleCSVUpload($file, $organizationId, $context, FileManager $fileManager)
    {
        // Upload file first
        $uploadedFile = $fileManager->upload($file, Auth::user(), 'csv_upload', $organizationId);
        
        // Parse CSV
        $path = $uploadedFile->storage_driver === 'google' 
            ? $fileManager->download($uploadedFile)
            : Storage::disk('public')->path($uploadedFile->storage_path);
        
        $rows = [];
        if (($handle = fopen($path, 'r')) !== false) {
            $headers = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== false) {
                $row = [];
                foreach ($headers as $index => $header) {
                    $row[trim($header)] = $data[$index] ?? '';
                }
                $rows[] = $row;
            }
            fclose($handle);
        }

        // Route based on context
        if ($context === 'transactions' || $context === 'expenses' || $context === 'income') {
            return $this->processTransactionCSV($rows, $organizationId, $uploadedFile, $context);
        }

        // Default: just store as attachment
        $attachment = Attachment::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $organizationId,
            'attachable_type' => null,
            'attachable_id' => null,
            'name' => $file->getClientOriginalName(),
            'file_path' => $uploadedFile->storage_path,
            'file_name' => $uploadedFile->file_name,
            'file_size' => $uploadedFile->file_size,
            'mime_type' => $uploadedFile->mime_type,
            'uploaded_by_id' => Auth::id(),
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'CSV uploaded successfully',
            'data' => [
                'attachment_id' => $attachment->id,
                'rows_processed' => count($rows),
            ]
        ]);
    }

    protected function processTransactionCSV($rows, $organizationId, $uploadedFile, $context = 'transactions')
    {
        $processed = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                // Try to identify transaction fields
                $amount = $this->extractAmount($row);
                $date = $this->extractDate($row);
                $description = $this->extractDescription($row);
                $type = $this->extractType($row, $amount);

                if (!$amount || !$date || !$description) {
                    $errors[] = "Row " . ($index + 2) . ": Missing required fields";
                    continue;
                }

                // Override type based on context if provided
                $finalType = $type;
                if ($context === 'expenses') {
                    $finalType = 'expense';
                } elseif ($context === 'income') {
                    $finalType = 'income';
                }

                // Determine account (you might want to make this configurable)
                $account = \App\Models\MoneyAccount::where('organization_id', $organizationId)
                    ->where('is_active', true)
                    ->first();

                if (!$account) {
                    $errors[] = "Row " . ($index + 2) . ": No active account found";
                    continue;
                }

                MoneyMovement::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $organizationId,
                    'flow_type' => $finalType,
                    'amount' => abs($amount),
                    'currency' => 'ZMW',
                    'transaction_date' => $date,
                    'from_account_id' => $finalType === 'expense' ? $account->id : null,
                    'to_account_id' => $finalType === 'income' ? $account->id : null,
                    'description' => $description,
                    'status' => 'approved',
                    'created_by_id' => Auth::id(),
                ]);

                $processed++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => "Processed {$processed} transactions" . (count($errors) > 0 ? " with " . count($errors) . " errors" : ""),
            'data' => [
                'processed' => $processed,
                'errors' => $errors,
            ]
        ]);
    }

    protected function extractAmount($row)
    {
        foreach (['amount', 'value', 'total', 'sum'] as $key) {
            if (isset($row[$key])) {
                $value = preg_replace('/[^0-9.-]/', '', $row[$key]);
                return floatval($value);
            }
        }
        return null;
    }

    protected function extractDate($row)
    {
        foreach (['date', 'transaction_date', 'payment_date'] as $key) {
            if (isset($row[$key])) {
                try {
                    return Carbon::parse($row[$key])->format('Y-m-d');
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
        return now()->format('Y-m-d');
    }

    protected function extractDescription($row)
    {
        foreach (['description', 'details', 'note', 'memo', 'reference'] as $key) {
            if (isset($row[$key]) && !empty($row[$key])) {
                return $row[$key];
            }
        }
        return 'Imported transaction';
    }

    protected function extractType($row, $amount)
    {
        if (isset($row['type'])) {
            $type = strtolower($row['type']);
            if (in_array($type, ['income', 'expense', 'transfer'])) {
                return $type;
            }
        }

        // Default based on amount sign
        return $amount >= 0 ? 'income' : 'expense';
    }

    protected function handleImageUpload($file, $organizationId, $context, FileManager $fileManager)
    {
        $uploadedFile = $fileManager->upload($file, Auth::user(), 'image_upload', $organizationId);

        // Create attachment
        $attachment = Attachment::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $organizationId,
            'attachable_type' => null,
            'attachable_id' => null,
            'name' => $file->getClientOriginalName(),
            'file_path' => $uploadedFile->storage_path,
            'file_name' => $uploadedFile->file_name,
            'file_size' => $uploadedFile->file_size,
            'mime_type' => $uploadedFile->mime_type,
            'uploaded_by_id' => Auth::id(),
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Image uploaded successfully',
            'data' => [
                'attachment_id' => $attachment->id,
                'url' => $uploadedFile->url ?? Storage::disk('public')->url($uploadedFile->storage_path),
            ]
        ]);
    }
}

