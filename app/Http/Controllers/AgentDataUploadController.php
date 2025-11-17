<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDocumentJob;
use App\Models\DocumentProcessingJob as ProcessingJobModel;
use App\Models\MoneyMovement;
use App\Models\MoneyAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Carbon\Carbon;

class AgentDataUploadController extends Controller
{
    /**
     * Show upload page
     */
    public function index()
    {
        return Inertia::render('DataUpload/Agent', [
            'recentJobs' => $this->getRecentJobs(auth()->user()),
        ]);
    }

    /**
     * Upload and queue document for processing
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'is_historical' => 'nullable',
        ]);

        $user = $request->user();
        $organizationId = session('current_organization_id') ?? $user->current_organization_id;
        
        // Store file temporarily
        $file = $request->file('file');
        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('temp/uploads', $fileName);
        $fullPath = Storage::path($filePath);

        // Create job ID
        $jobId = Str::uuid();

        // Create job record first
        $job = ProcessingJobModel::create([
            'id' => $jobId,
            'organization_id' => $organizationId,
            'user_id' => $user->id,
            'file_path' => $fullPath,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'status' => 'pending',
            'status_message' => 'Queued for processing',
            'metadata' => [
                'original_name' => $file->getClientOriginalName(),
                'is_historical' => filter_var($request->input('is_historical', false), FILTER_VALIDATE_BOOLEAN),
                'uploaded_at' => now()->toIso8601String(),
            ],
            'started_at' => now(),
        ]);

        // Dispatch job
        ProcessDocumentJob::dispatch(
            $jobId,
            $fullPath,
            $organizationId,
            $user->id,
            $job->metadata
        );

        return response()->json([
            'success' => true,
            'job_id' => $jobId,
            'message' => 'Document queued for processing. You\'ll be notified when it\'s done!',
        ]);
    }

    /**
     * Check job status
     */
    public function status(string $jobId)
    {
        $job = ProcessingJobModel::find($jobId);

        if (!$job) {
            return response()->json([
                'error' => 'Job not found',
            ], 404);
        }

        // Check permission
        if ($job->user_id !== auth()->id()) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'job_id' => $job->id,
            'status' => $job->status,
            'status_message' => $job->status_message,
            'progress' => $job->progress,
            'is_complete' => $job->isComplete(),
            'is_failed' => $job->isFailed(),
            'result' => $job->result,
            'error' => $job->error,
            'processing_time' => $job->processing_time,
            'created_at' => $job->created_at->toIso8601String(),
            'completed_at' => $job->completed_at?->toIso8601String(),
        ]);
    }

    /**
     * Import reviewed data
     */
    public function importReviewed(Request $request, string $jobId)
    {
        $request->validate([
            'reviewed_data' => 'required|array',
        ]);

        $job = ProcessingJobModel::find($jobId);

        if (!$job) {
            return response()->json([
                'error' => 'Job not found',
            ], 404);
        }

        // Check permission
        if ($job->user_id !== auth()->id()) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 403);
        }

        // Merge reviewed data with original
        $data = array_merge(
            $job->result['data'] ?? [],
            $request->reviewed_data
        );

        $documentType = $data['document_type'] ?? $job->result['document_type'] ?? 'unknown';

        // Import based on type
        try {
            $result = $this->importDocument(
                $request->user(),
                $job->organization_id,
                $documentType,
                $data
            );

            // Update job
            $job->update([
                'status' => 'completed',
                'status_message' => 'Document imported after review',
                'result' => array_merge($job->result ?? [], [
                    'imported' => true,
                    'import_result' => $result,
                    'reviewed_data' => $request->reviewed_data,
                ]),
                'completed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? 'Document imported successfully',
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get recent jobs for user
     */
    protected function getRecentJobs($user, int $limit = 10): array
    {
        return ProcessingJobModel::where('user_id', $user->id)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'file_name' => $job->file_name ?? ($job->metadata['original_name'] ?? 'Unknown'),
                    'status' => $job->status,
                    'status_message' => $job->status_message,
                    'progress' => $job->progress,
                    'document_type' => $job->result['document_type'] ?? null,
                    'confidence' => $job->result['confidence'] ?? null,
                    'requires_review' => $job->result['requires_review'] ?? false,
                    'created_at' => $job->created_at->diffForHumans(),
                    'completed_at' => $job->completed_at?->diffForHumans(),
                    'processing_time' => $job->processing_time,
                ];
            })
            ->toArray();
    }

    /**
     * Import document based on type
     */
    protected function importDocument($user, $organizationId, string $documentType, array $data): array
    {
        switch ($documentType) {
            case 'bank_statement':
                return $this->importBankStatement($user, $organizationId, $data);
            
            case 'receipt':
            case 'expense':
                return $this->importReceipt($user, $organizationId, $data);
            
            case 'invoice':
                return $this->importInvoice($user, $organizationId, $data);
            
            case 'mobile_money':
                return $this->importMobileMoney($user, $organizationId, $data);
            
            case 'income':
                return $this->importIncome($user, $organizationId, $data);
            
            default:
                throw new \Exception("Unknown document type: {$documentType}");
        }
    }

    /**
     * Import bank statement
     */
    protected function importBankStatement($user, $organizationId, array $data): array
    {
        try {
            // Get or create bank account
            $accountNumber = $data['account_number'] ?? null;
            $account = null;
            
            if ($accountNumber) {
                $account = MoneyAccount::where('organization_id', $organizationId)
                    ->where('account_number', $accountNumber)
                    ->where('type', 'bank')
                    ->first();
            }
            
            if (!$account) {
                $account = MoneyAccount::where('organization_id', $organizationId)
                    ->where('type', 'bank')
                    ->first();
            }

            if (!$account) {
                $account = MoneyAccount::create([
                    'id' => Str::uuid(),
                    'organization_id' => $organizationId,
                    'name' => $data['bank_name'] ?? 'Bank Account',
                    'account_number' => $accountNumber,
                    'type' => 'bank',
                    'currency' => $data['currency'] ?? 'ZMW',
                ]);
            }

            $transactions = $data['transactions'] ?? [];
            $imported = 0;
            $failed = 0;
            $errors = [];

            foreach ($transactions as $tx) {
                try {
                    $flowType = $tx['flow_type'] ?? ($tx['type'] === 'credit' ? 'income' : 'expense');
                    $amount = abs($tx['amount'] ?? 0);
                    
                    if ($amount <= 0) {
                        continue;
                    }

                    $movement = MoneyMovement::create([
                        'id' => Str::uuid(),
                        'organization_id' => $organizationId,
                        'flow_type' => $flowType,
                        'amount' => $amount,
                        'currency' => $data['currency'] ?? 'ZMW',
                        'transaction_date' => isset($tx['date']) ? Carbon::parse($tx['date']) : now(),
                        'to_account_id' => $flowType === 'income' ? $account->id : null,
                        'from_account_id' => $flowType === 'expense' ? $account->id : null,
                        'description' => $tx['description'] ?? 'Bank statement transaction',
                        'category' => $tx['category'] ?? 'Banking',
                        'status' => 'completed',
                        'created_by_id' => $user->id,
                    ]);
                    
                    $imported++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Transaction failed: " . $e->getMessage();
                }
            }

            return [
                'success' => true,
                'message' => "Bank statement imported: {$imported} transactions imported" . ($failed > 0 ? ", {$failed} failed" : ""),
                'imported' => $imported,
                'failed' => $failed,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Import receipt
     */
    protected function importReceipt($user, $organizationId, array $data): array
    {
        try {
            $account = MoneyAccount::where('organization_id', $organizationId)
                ->where('type', 'expense')
                ->first();

            if (!$account) {
                $account = MoneyAccount::create([
                    'id' => Str::uuid(),
                    'organization_id' => $organizationId,
                    'name' => 'Expenses',
                    'type' => 'expense',
                    'currency' => $data['currency'] ?? 'ZMW',
                ]);
            }

            $movement = MoneyMovement::create([
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'flow_type' => 'expense',
                'amount' => abs($data['total'] ?? $data['amount'] ?? 0),
                'currency' => $data['currency'] ?? 'ZMW',
                'transaction_date' => isset($data['date']) ? Carbon::parse($data['date']) : now(),
                'from_account_id' => $account->id,
                'description' => $this->generateDescription($data),
                'category' => $data['category'] ?? 'Uncategorized',
                'status' => 'completed',
                'created_by_id' => $user->id,
            ]);

            return [
                'success' => true,
                'message' => 'Receipt imported successfully',
                'movement_id' => $movement->id,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Import income
     */
    protected function importIncome($user, $organizationId, array $data): array
    {
        try {
            $account = MoneyAccount::where('organization_id', $organizationId)
                ->where('type', 'income')
                ->first();

            if (!$account) {
                $account = MoneyAccount::create([
                    'id' => Str::uuid(),
                    'organization_id' => $organizationId,
                    'name' => 'Income',
                    'type' => 'income',
                    'currency' => $data['currency'] ?? 'ZMW',
                ]);
            }

            $movement = MoneyMovement::create([
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'flow_type' => 'income',
                'amount' => abs($data['amount'] ?? $data['total'] ?? 0),
                'currency' => $data['currency'] ?? 'ZMW',
                'transaction_date' => isset($data['date']) ? Carbon::parse($data['date']) : now(),
                'to_account_id' => $account->id,
                'description' => $this->generateDescription($data),
                'category' => $data['category'] ?? 'Income',
                'status' => 'completed',
                'created_by_id' => $user->id,
            ]);

            return [
                'success' => true,
                'message' => 'Income imported successfully',
                'movement_id' => $movement->id,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Import invoice
     */
    protected function importInvoice($user, $organizationId, array $data): array
    {
        // For now, treat invoices as expenses
        return $this->importReceipt($user, $organizationId, $data);
    }

    /**
     * Import mobile money
     */
    protected function importMobileMoney($user, $organizationId, array $data): array
    {
        try {
            $type = $data['flow_type'] ?? ($data['type'] === 'income' ? 'income' : 'expense');

            $account = MoneyAccount::where('organization_id', $organizationId)
                ->where('name', 'LIKE', '%Mobile Money%')
                ->first();

            if (!$account) {
                $account = MoneyAccount::create([
                    'id' => Str::uuid(),
                    'organization_id' => $organizationId,
                    'name' => 'Mobile Money',
                    'type' => $type === 'income' ? 'income' : 'expense',
                    'currency' => $data['currency'] ?? 'ZMW',
                ]);
            }

            $movement = MoneyMovement::create([
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'flow_type' => $type,
                'amount' => abs($data['amount'] ?? 0),
                'currency' => $data['currency'] ?? 'ZMW',
                'transaction_date' => isset($data['date']) ? Carbon::parse($data['date']) : now(),
                'to_account_id' => $type === 'income' ? $account->id : null,
                'from_account_id' => $type === 'expense' ? $account->id : null,
                'description' => $data['transaction_context'] ?? (($data['provider'] ?? 'Mobile Money') . ' transaction'),
                'category' => $data['category'] ?? 'Mobile Money',
                'status' => 'completed',
                'created_by_id' => $user->id,
            ]);

            return [
                'success' => true,
                'message' => 'Mobile money transaction imported successfully',
                'movement_id' => $movement->id,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate description from data
     */
    protected function generateDescription(array $data): string
    {
        $parts = [];

        if (isset($data['merchant'])) {
            $parts[] = 'Purchase from ' . $data['merchant'];
        } elseif (isset($data['vendor'])) {
            $parts[] = 'Payment to ' . $data['vendor'];
        } elseif (isset($data['transaction_context'])) {
            $parts[] = $data['transaction_context'];
        }

        if (isset($data['items']) && count($data['items']) > 0) {
            $itemNames = array_slice(array_column($data['items'], 'name'), 0, 3);
            if (count($itemNames) > 0) {
                $parts[] = '(' . implode(', ', $itemNames) . ')';
            }
        }

        if (empty($parts) && isset($data['description'])) {
            return $data['description'];
        }

        return implode(' ', $parts) ?: 'Transaction';
    }

    /**
     * Cancel job
     */
    public function cancel(string $jobId)
    {
        $job = ProcessingJobModel::find($jobId);

        if (!$job) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        if ($job->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($job->isComplete() || $job->isFailed()) {
            return response()->json([
                'error' => 'Cannot cancel completed or failed job',
            ], 400);
        }

        $job->update([
            'status' => 'failed',
            'status_message' => 'Cancelled by user',
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job cancelled',
        ]);
    }
}

