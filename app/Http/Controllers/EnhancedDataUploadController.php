<?php

namespace App\Http\Controllers;

use App\Services\ContextAwareOcrService;
use App\Models\MoneyMovement;
use App\Models\MoneyAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon;

class EnhancedDataUploadController extends Controller
{
    protected ContextAwareOcrService $ocrService;

    public function __construct(ContextAwareOcrService $ocrService)
    {
        $this->ocrService = $ocrService;
    }

    /**
     * Show upload page
     */
    public function index()
    {
        return Inertia::render('DataUpload/Enhanced', [
            'templates' => $this->getTemplates(),
        ]);
    }

    /**
     * Analyze uploaded file with context awareness
     */
    public function analyze(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,pdf,jpg,jpeg,png|max:10240',
            'is_historical' => 'boolean',
        ]);

        $file = $request->file('file');
        $filePath = $file->store('temp/uploads');
        $fullPath = Storage::path($filePath);

        $extension = $file->getClientOriginalExtension();
        $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'pdf']);
        $isHistorical = $request->boolean('is_historical', false);

        if ($isImage) {
            // Use context-aware OCR
            $organizationId = session('current_organization_id') ?? $request->user()->current_organization_id;
            $result = $this->ocrService->processDocumentWithContext(
                $fullPath,
                [
                    'user_id' => $request->user()->id,
                    'organization_id' => $organizationId,
                ],
                $isHistorical
            );
            
            return response()->json([
                'success' => true,
                'method' => 'ocr',
                'file_path' => $filePath,
                'analysis' => $result,
            ]);
        } else {
            // CSV detection - to be implemented
            return response()->json([
                'success' => false,
                'error' => 'CSV processing not yet implemented',
            ]);
        }
    }

    /**
     * Import OCR with reviewed data
     */
    public function importOcrReviewed(Request $request)
    {
        $request->validate([
            'file_path' => 'required|string',
            'document_type' => 'required|string',
            'data' => 'required|array',
            'reviewed' => 'boolean',
        ]);

        $user = $request->user();
        $organizationId = session('current_organization_id') ?? $user->current_organization_id;
        $data = $request->data;

        // Import based on document type
        $result = match($data['type'] ?? $request->document_type) {
            'receipt', 'expense' => $this->importReceipt($user, $organizationId, $data),
            'income' => $this->importIncome($user, $organizationId, $data),
            'invoice' => $this->importInvoice($user, $organizationId, $data),
            'mobile_money' => $this->importMobileMoneyTransaction($user, $organizationId, $data),
            'bank_statement' => $this->importBankTransaction($user, $organizationId, $data),
            default => ['success' => false, 'message' => 'Unknown document type'],
        };

        // Clean up temp file
        if (Storage::exists($request->file_path)) {
            Storage::delete($request->file_path);
        }

        // Add metadata about review
        if ($request->boolean('reviewed')) {
            $result['reviewed'] = true;
            $result['review_timestamp'] = now()->toIso8601String();
        }

        return response()->json($result);
    }

    /**
     * Batch process historical documents
     */
    public function batchHistorical(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $user = $request->user();
        $filePaths = [];

        // Store all files first
        foreach ($request->file('files') as $file) {
            $path = $file->store('temp/uploads');
            $filePaths[] = Storage::path($path);
        }

        // Batch process with context awareness
        $results = $this->ocrService->batchProcessHistorical($filePaths, $user);

        return response()->json([
            'success' => true,
            'batch_results' => $results,
            'message' => sprintf(
                "Processed %d documents. %d ready to import, %d need review.",
                $results['total'],
                $results['auto_importable'],
                $results['needs_review']
            ),
        ]);
    }

    /**
     * Auto-import documents that don't need review
     */
    public function autoImportBatch(Request $request)
    {
        $request->validate([
            'documents' => 'required|array',
        ]);

        $user = $request->user();
        $organizationId = session('current_organization_id') ?? $user->current_organization_id;
        $imported = 0;
        $failed = 0;
        $errors = [];

        foreach ($request->documents as $doc) {
            if (!($doc['auto_importable'] ?? false)) {
                continue;
            }

            try {
                $result = match($doc['document_type']) {
                    'receipt', 'expense' => $this->importReceipt($user, $organizationId, $doc['data']),
                    'income' => $this->importIncome($user, $organizationId, $doc['data']),
                    'mobile_money' => $this->importMobileMoneyTransaction($user, $organizationId, $doc['data']),
                    default => ['success' => false],
                };

                if ($result['success']) {
                    $imported++;
                } else {
                    $failed++;
                    $errors[] = $result['message'] ?? 'Unknown error';
                }
            } catch (\Exception $e) {
                $failed++;
                $errors[] = $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'imported' => $imported,
            'failed' => $failed,
            'errors' => $errors,
        ]);
    }

    /**
     * Get user's transaction history for context
     */
    public function getContext(Request $request)
    {
        $user = $request->user();
        $organizationId = session('current_organization_id') ?? $user->current_organization_id;

        return response()->json([
            'recent_merchants' => $this->getRecentMerchants($user, $organizationId),
            'common_categories' => $this->getCommonCategories($user, $organizationId),
            'typical_amounts' => $this->getTypicalAmounts($user, $organizationId),
        ]);
    }

    /**
     * Import receipt as expense transaction
     */
    protected function importReceipt($user, $organizationId, array $data): array
    {
        try {
            // Get or create default expense account
            $account = MoneyAccount::where('organization_id', $organizationId)
                ->where('type', 'expense')
                ->first();

            if (!$account) {
                // Create default expense account
                $account = MoneyAccount::create([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'organization_id' => $organizationId,
                    'name' => 'Expenses',
                    'type' => 'expense',
                    'currency' => $data['currency'] ?? 'ZMW',
                ]);
            }

            $movement = MoneyMovement::create([
                'id' => \Illuminate\Support\Str::uuid(),
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
                'type' => 'expense',
            ];
        } catch (\Exception $e) {
            \Log::error('Receipt import failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Import income transaction
     */
    protected function importIncome($user, $organizationId, array $data): array
    {
        try {
            // Get or create default income account
            $account = MoneyAccount::where('organization_id', $organizationId)
                ->where('type', 'income')
                ->first();

            if (!$account) {
                $account = MoneyAccount::create([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'organization_id' => $organizationId,
                    'name' => 'Income',
                    'type' => 'income',
                    'currency' => $data['currency'] ?? 'ZMW',
                ]);
            }

            $movement = MoneyMovement::create([
                'id' => \Illuminate\Support\Str::uuid(),
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
                'type' => 'income',
            ];
        } catch (\Exception $e) {
            \Log::error('Income import failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate smart description from data
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
     * Import invoice
     */
    protected function importInvoice($user, $organizationId, array $data): array
    {
        // For now, treat invoices as expenses
        return $this->importReceipt($user, $organizationId, $data);
    }

    /**
     * Import mobile money transaction
     */
    protected function importMobileMoneyTransaction($user, $organizationId, array $data): array
    {
        try {
            // Determine if income or expense based on context
            $type = $this->determineMobileMoneyType($data);

            // Get or create mobile money account
            $account = MoneyAccount::where('organization_id', $organizationId)
                ->where('name', 'LIKE', '%Mobile Money%')
                ->first();

            if (!$account) {
                $account = MoneyAccount::create([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'organization_id' => $organizationId,
                    'name' => 'Mobile Money',
                    'type' => $type === 'income' ? 'income' : 'expense',
                    'currency' => $data['currency'] ?? 'ZMW',
                ]);
            }

            $movement = MoneyMovement::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'organization_id' => $organizationId,
                'flow_type' => $type,
                'amount' => abs($data['amount'] ?? 0),
                'currency' => $data['currency'] ?? 'ZMW',
                'transaction_date' => isset($data['date']) ? Carbon::parse($data['date']) : now(),
                'to_account_id' => $type === 'income' ? $account->id : null,
                'from_account_id' => $type === 'expense' ? $account->id : null,
                'description' => $data['transaction_context'] ?? 
                    (($data['provider'] ?? 'Mobile Money') . ' transaction'),
                'category' => $data['category'] ?? 'Mobile Money',
                'status' => 'completed',
                'created_by_id' => $user->id,
            ]);

            return [
                'success' => true,
                'message' => 'Mobile money transaction imported successfully',
                'movement_id' => $movement->id,
                'type' => $type,
            ];
        } catch (\Exception $e) {
            \Log::error('Mobile money import failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Determine mobile money transaction type
     */
    protected function determineMobileMoneyType(array $data): string
    {
        // If user explicitly set type, use that
        if (isset($data['type']) && in_array($data['type'], ['income', 'expense'])) {
            return $data['type'];
        }

        // If transaction context mentions payment received, it's income
        if (isset($data['transaction_context'])) {
            $context = strtolower($data['transaction_context']);
            if (str_contains($context, 'payment') || 
                str_contains($context, 'received') ||
                str_contains($context, 'customer')) {
                return 'income';
            }
        }

        // Default to income for mobile money (most business cases)
        return 'income';
    }

    /**
     * Import bank transaction
     */
    protected function importBankTransaction($user, $organizationId, array $data): array
    {
        try {
            // Get or create bank account
            $account = MoneyAccount::where('organization_id', $organizationId)
                ->where('type', 'bank')
                ->first();

            if (!$account) {
                $account = MoneyAccount::create([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'organization_id' => $organizationId,
                    'name' => $data['bank_name'] ?? 'Bank Account',
                    'type' => 'bank',
                    'currency' => $data['currency'] ?? 'ZMW',
                ]);
            }

            $type = $data['type'] ?? 'expense';
            $movement = MoneyMovement::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'organization_id' => $organizationId,
                'flow_type' => $type,
                'amount' => abs($data['amount'] ?? 0),
                'currency' => $data['currency'] ?? 'ZMW',
                'transaction_date' => isset($data['date']) ? Carbon::parse($data['date']) : now(),
                'to_account_id' => $type === 'income' ? $account->id : null,
                'from_account_id' => $type === 'expense' ? $account->id : null,
                'description' => $data['description'] ?? 'Bank transaction',
                'category' => $data['category'] ?? 'Banking',
                'status' => 'completed',
                'created_by_id' => $user->id,
            ]);

            return [
                'success' => true,
                'message' => 'Bank transaction imported successfully',
                'movement_id' => $movement->id,
            ];
        } catch (\Exception $e) {
            \Log::error('Bank transaction import failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get recent merchants for context
     */
    protected function getRecentMerchants($user, $organizationId): array
    {
        return MoneyMovement::where('organization_id', $organizationId)
            ->whereNotNull('description')
            ->latest()
            ->take(20)
            ->get()
            ->pluck('description')
            ->map(function ($desc) {
                // Extract merchant name from description
                if (preg_match('/from (.+?)(?:$|,|\(|Purchase)/i', $desc, $matches)) {
                    return trim($matches[1]);
                }
                return null;
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Get common categories
     */
    protected function getCommonCategories($user, $organizationId): array
    {
        return MoneyMovement::where('organization_id', $organizationId)
            ->whereNotNull('category')
            ->groupBy('category')
            ->selectRaw('category, count(*) as count')
            ->orderByDesc('count')
            ->take(10)
            ->pluck('category')
            ->toArray();
    }

    /**
     * Get typical amount ranges
     */
    protected function getTypicalAmounts($user, $organizationId): array
    {
        $stats = MoneyMovement::where('organization_id', $organizationId)
            ->selectRaw('AVG(amount) as avg, MIN(amount) as min, MAX(amount) as max')
            ->first();

        return [
            'average' => round($stats->avg ?? 0, 2),
            'min' => round($stats->min ?? 0, 2),
            'max' => round($stats->max ?? 0, 2),
        ];
    }

    /**
     * Get templates
     */
    protected function getTemplates(): array
    {
        return [
            [
                'id' => 'transactions',
                'name' => 'Transactions',
                'description' => 'Import income and expenses',
                'icon' => 'receipt',
            ],
            [
                'id' => 'customers',
                'name' => 'Customers',
                'description' => 'Import customer database',
                'icon' => 'users',
            ],
            [
                'id' => 'products',
                'name' => 'Products',
                'description' => 'Import product catalog',
                'icon' => 'package',
            ],
            [
                'id' => 'sales',
                'name' => 'Sales',
                'description' => 'Import sales records',
                'icon' => 'trending-up',
            ],
        ];
    }
}

