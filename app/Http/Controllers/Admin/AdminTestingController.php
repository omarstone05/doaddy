<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminTestingController extends Controller
{
    /**
     * Display the testing dashboard
     */
    public function index()
    {
        return Inertia::render('Admin/Testing/Index', [
            'existingTests' => $this->getExistingTests(),
            'requiredTests' => $this->getRequiredTests(),
            'completeness' => $this->getCompletenessReport(),
            'lastTestRun' => $this->getLastTestRun(),
        ]);
    }

    /**
     * Run all tests
     */
    public function runTests(Request $request)
    {
        $type = $request->input('type', 'all'); // all, unit, feature
        
        try {
            $command = 'test';
            $params = ['--no-interaction' => true];
            
            if ($type === 'unit') {
                $params['--testsuite'] = 'Unit';
            } elseif ($type === 'feature') {
                $params['--testsuite'] = 'Feature';
            }
            
            $exitCode = Artisan::call($command, $params);
            $output = Artisan::output();
            
            return response()->json([
                'success' => $exitCode === 0,
                'output' => $output,
                'exitCode' => $exitCode,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all existing tests
     */
    protected function getExistingTests(): array
    {
        $tests = [
            'unit' => [],
            'feature' => [],
            'integration' => [],
        ];

        // Unit tests
        $unitPath = base_path('tests/Unit');
        if (File::isDirectory($unitPath)) {
            $tests['unit'] = $this->scanTestDirectory($unitPath, 'Unit');
        }

        // Feature tests
        $featurePath = base_path('tests/Feature');
        if (File::isDirectory($featurePath)) {
            $tests['feature'] = $this->scanTestDirectory($featurePath, 'Feature');
        }

        // Integration tests
        $integrationPath = base_path('tests/Integration');
        if (File::isDirectory($integrationPath)) {
            $tests['integration'] = $this->scanTestDirectory($integrationPath, 'Integration');
        }

        return $tests;
    }

    /**
     * Scan a test directory recursively
     */
    protected function scanTestDirectory(string $path, string $type, string $prefix = ''): array
    {
        $tests = [];
        $files = File::files($path);
        $directories = File::directories($path);

        foreach ($files as $file) {
            if (str_ends_with($file->getFilename(), 'Test.php')) {
                $className = str_replace('.php', '', $file->getFilename());
                $fullPath = $prefix ? "{$prefix}/{$className}" : $className;
                
                // Parse the test file to get test methods
                $content = File::get($file->getPathname());
                preg_match_all('/public function (test_\w+|it_\w+)\(/', $content, $matches);
                
                $tests[] = [
                    'name' => $className,
                    'path' => $fullPath,
                    'file' => "tests/{$type}/" . ($prefix ? "{$prefix}/" : '') . $file->getFilename(),
                    'methods' => $matches[1] ?? [],
                    'methodCount' => count($matches[1] ?? []),
                    'type' => $type,
                ];
            }
        }

        foreach ($directories as $dir) {
            $dirName = basename($dir);
            $subTests = $this->scanTestDirectory($dir, $type, $prefix ? "{$prefix}/{$dirName}" : $dirName);
            $tests = array_merge($tests, $subTests);
        }

        return $tests;
    }

    /**
     * Get required tests that should be created
     */
    protected function getRequiredTests(): array
    {
        return [
            'unit' => [
                [
                    'name' => 'UserModelTest',
                    'description' => 'Test user model relationships and methods',
                    'priority' => 'high',
                    'status' => $this->testExists('Unit/UserModelTest') ? 'created' : 'pending',
                    'category' => 'Models',
                ],
                [
                    'name' => 'OrganizationModelTest',
                    'description' => 'Test organization model and multi-tenancy',
                    'priority' => 'high',
                    'status' => $this->testExists('Unit/OrganizationModelTest') ? 'created' : 'pending',
                    'category' => 'Models',
                ],
                [
                    'name' => 'InvoiceModelTest',
                    'description' => 'Test invoice calculations, status changes',
                    'priority' => 'high',
                    'status' => $this->testExists('Unit/InvoiceModelTest') ? 'created' : 'pending',
                    'category' => 'Models',
                ],
                [
                    'name' => 'PaymentModelTest',
                    'description' => 'Test payment allocations and balance updates',
                    'priority' => 'high',
                    'status' => $this->testExists('Unit/PaymentModelTest') ? 'created' : 'pending',
                    'category' => 'Models',
                ],
                [
                    'name' => 'CustomerModelTest',
                    'description' => 'Test customer relationships and calculations',
                    'priority' => 'medium',
                    'status' => $this->testExists('Unit/CustomerModelTest') ? 'created' : 'pending',
                    'category' => 'Models',
                ],
                [
                    'name' => 'TeamMemberModelTest',
                    'description' => 'Test team member and user relationships',
                    'priority' => 'medium',
                    'status' => $this->testExists('Unit/TeamMemberModelTest') ? 'created' : 'pending',
                    'category' => 'Models',
                ],
                [
                    'name' => 'AddyCommandParserTest',
                    'description' => 'Test Addy intent parsing',
                    'priority' => 'high',
                    'status' => $this->testExists('Unit/AddyCommandParserTest') ? 'created' : 'pending',
                    'category' => 'Addy AI',
                ],
                [
                    'name' => 'AddyResponseGeneratorTest',
                    'description' => 'Test Addy response generation',
                    'priority' => 'medium',
                    'status' => $this->testExists('Unit/AddyResponseGeneratorTest') ? 'created' : 'pending',
                    'category' => 'Addy AI',
                ],
                [
                    'name' => 'AddyPredictiveEngineTest',
                    'description' => 'Test prediction calculations',
                    'priority' => 'medium',
                    'status' => $this->testExists('Unit/AddyPredictiveEngineTest') ? 'created' : 'pending',
                    'category' => 'Addy AI',
                ],
                [
                    'name' => 'AddyCulturalEngineTest',
                    'description' => 'Test cultural context adaptation',
                    'priority' => 'low',
                    'status' => $this->testExists('Unit/AddyCulturalEngineTest') ? 'created' : 'pending',
                    'category' => 'Addy AI',
                ],
            ],
            'feature' => [
                [
                    'name' => 'AuthenticationTest',
                    'description' => 'Test login, logout, registration flows',
                    'priority' => 'critical',
                    'status' => $this->testExists('Feature/AuthenticationTest') ? 'created' : 'pending',
                    'category' => 'Authentication',
                ],
                [
                    'name' => 'GoogleOAuthTest',
                    'description' => 'Test Google OAuth login flow',
                    'priority' => 'high',
                    'status' => $this->testExists('Feature/GoogleOAuthTest') ? 'created' : 'pending',
                    'category' => 'Authentication',
                ],
                [
                    'name' => 'OrganizationSwitchingTest',
                    'description' => 'Test organization switching functionality',
                    'priority' => 'high',
                    'status' => $this->testExists('Feature/OrganizationSwitchingTest') ? 'created' : 'pending',
                    'category' => 'Organization',
                ],
                [
                    'name' => 'CustomerCrudTest',
                    'description' => 'Test customer CRUD operations',
                    'priority' => 'high',
                    'status' => $this->testExists('Feature/CustomerCrudTest') ? 'created' : 'pending',
                    'category' => 'Sales',
                ],
                [
                    'name' => 'InvoiceCrudTest',
                    'description' => 'Test invoice CRUD and PDF generation',
                    'priority' => 'critical',
                    'status' => $this->testExists('Feature/InvoiceCrudTest') ? 'created' : 'pending',
                    'category' => 'Sales',
                ],
                [
                    'name' => 'PaymentCrudTest',
                    'description' => 'Test payment recording and allocation',
                    'priority' => 'critical',
                    'status' => $this->testExists('Feature/PaymentCrudTest') ? 'created' : 'pending',
                    'category' => 'Sales',
                ],
                [
                    'name' => 'QuotationCrudTest',
                    'description' => 'Test quotation CRUD and conversion',
                    'priority' => 'high',
                    'status' => $this->testExists('Feature/QuotationCrudTest') ? 'created' : 'pending',
                    'category' => 'Sales',
                ],
                [
                    'name' => 'TransactionCrudTest',
                    'description' => 'Test transaction recording',
                    'priority' => 'high',
                    'status' => $this->testExists('Feature/TransactionCrudTest') ? 'created' : 'pending',
                    'category' => 'Money',
                ],
                [
                    'name' => 'MoneyAccountCrudTest',
                    'description' => 'Test bank account management',
                    'priority' => 'high',
                    'status' => $this->testExists('Feature/MoneyAccountCrudTest') ? 'created' : 'pending',
                    'category' => 'Money',
                ],
                [
                    'name' => 'VendorCrudTest',
                    'description' => 'Test vendor CRUD operations',
                    'priority' => 'medium',
                    'status' => $this->testExists('Feature/VendorCrudTest') ? 'created' : 'pending',
                    'category' => 'Expenses',
                ],
                [
                    'name' => 'BillCrudTest',
                    'description' => 'Test bill CRUD and payments',
                    'priority' => 'medium',
                    'status' => $this->testExists('Feature/BillCrudTest') ? 'created' : 'pending',
                    'category' => 'Expenses',
                ],
                [
                    'name' => 'ProductCrudTest',
                    'description' => 'Test product/service CRUD',
                    'priority' => 'high',
                    'status' => $this->testExists('Feature/ProductCrudTest') ? 'created' : 'pending',
                    'category' => 'Inventory',
                ],
                [
                    'name' => 'StockMovementTest',
                    'description' => 'Test stock adjustments and movements',
                    'priority' => 'medium',
                    'status' => $this->testExists('Feature/StockMovementTest') ? 'created' : 'pending',
                    'category' => 'Inventory',
                ],
                [
                    'name' => 'TeamMemberCrudTest',
                    'description' => 'Test team member management',
                    'priority' => 'high',
                    'status' => $this->testExists('Feature/TeamMemberCrudTest') ? 'created' : 'pending',
                    'category' => 'HR',
                ],
                [
                    'name' => 'LeaveRequestFlowTest',
                    'description' => 'Test leave request workflow',
                    'priority' => 'high',
                    'status' => $this->testExists('Feature/LeaveRequestFlowTest') ? 'created' : 'pending',
                    'category' => 'HR',
                ],
                [
                    'name' => 'PayrollRunTest',
                    'description' => 'Test payroll run creation and processing',
                    'priority' => 'high',
                    'status' => $this->testExists('Feature/PayrollRunTest') ? 'created' : 'pending',
                    'category' => 'HR',
                ],
                [
                    'name' => 'ReportsTest',
                    'description' => 'Test report generation',
                    'priority' => 'medium',
                    'status' => $this->testExists('Feature/ReportsTest') ? 'created' : 'pending',
                    'category' => 'Reports',
                ],
                [
                    'name' => 'AdminPanelAccessTest',
                    'description' => 'Test super admin access controls',
                    'priority' => 'critical',
                    'status' => $this->testExists('Feature/AdminPanelAccessTest') ? 'created' : 'pending',
                    'category' => 'Admin',
                ],
                [
                    'name' => 'SupportTicketTest',
                    'description' => 'Test support ticket system',
                    'priority' => 'medium',
                    'status' => $this->testExists('Feature/SupportTicketTest') ? 'created' : 'pending',
                    'category' => 'Support',
                ],
            ],
            'integration' => [
                [
                    'name' => 'AddyCoreServiceTest',
                    'description' => 'Test Addy decision loop integration',
                    'priority' => 'high',
                    'status' => $this->testExists('Integration/AddyCoreServiceTest') ? 'created' : 'pending',
                    'category' => 'Addy AI',
                ],
                [
                    'name' => 'AddyActionExecutionTest',
                    'description' => 'Test Addy action execution end-to-end',
                    'priority' => 'high',
                    'status' => $this->testExists('Integration/AddyActionExecutionTest') ? 'created' : 'pending',
                    'category' => 'Addy AI',
                ],
                [
                    'name' => 'InvoicePaymentFlowTest',
                    'description' => 'Test complete invoice to payment flow',
                    'priority' => 'critical',
                    'status' => $this->testExists('Integration/InvoicePaymentFlowTest') ? 'created' : 'pending',
                    'category' => 'Sales',
                ],
                [
                    'name' => 'QuoteToInvoiceFlowTest',
                    'description' => 'Test quote conversion to invoice',
                    'priority' => 'high',
                    'status' => $this->testExists('Integration/QuoteToInvoiceFlowTest') ? 'created' : 'pending',
                    'category' => 'Sales',
                ],
                [
                    'name' => 'CacheIntegrationTest',
                    'description' => 'Test Redis caching integration',
                    'priority' => 'medium',
                    'status' => $this->testExists('Integration/CacheIntegrationTest') ? 'created' : 'pending',
                    'category' => 'Infrastructure',
                ],
            ],
        ];
    }

    /**
     * Check if a test file exists
     */
    protected function testExists(string $path): bool
    {
        return File::exists(base_path("tests/{$path}.php"));
    }

    /**
     * Get completeness report
     */
    protected function getCompletenessReport(): array
    {
        return [
            'modules' => $this->getModuleCompleteness(),
            'features' => $this->getFeatureCompleteness(),
            'documentation' => $this->getDocumentationCompleteness(),
            'security' => $this->getSecurityCompleteness(),
            'deployment' => $this->getDeploymentCompleteness(),
        ];
    }

    /**
     * Get module completeness
     */
    protected function getModuleCompleteness(): array
    {
        return [
            [
                'name' => 'Money Management',
                'percentage' => 95,
                'items' => [
                    ['name' => 'Bank Accounts CRUD', 'status' => 'complete'],
                    ['name' => 'Transactions', 'status' => 'complete'],
                    ['name' => 'Income Tracking', 'status' => 'complete'],
                    ['name' => 'Expense Tracking', 'status' => 'complete'],
                    ['name' => 'Receipt Upload', 'status' => 'complete'],
                    ['name' => 'Transaction Verification', 'status' => 'complete'],
                ],
            ],
            [
                'name' => 'Sales',
                'percentage' => 90,
                'items' => [
                    ['name' => 'Customers CRUD', 'status' => 'complete'],
                    ['name' => 'Prospects CRUD', 'status' => 'complete'],
                    ['name' => 'Quotations CRUD', 'status' => 'complete'],
                    ['name' => 'Invoices CRUD', 'status' => 'complete'],
                    ['name' => 'Invoice PDF Download', 'status' => 'complete'],
                    ['name' => 'Invoice Email Sending', 'status' => 'incomplete', 'note' => 'TODO in code'],
                    ['name' => 'Payments & Allocations', 'status' => 'complete'],
                    ['name' => 'Commissions', 'status' => 'complete'],
                ],
            ],
            [
                'name' => 'Expenses',
                'percentage' => 95,
                'items' => [
                    ['name' => 'Vendors CRUD', 'status' => 'complete'],
                    ['name' => 'Bills CRUD', 'status' => 'complete'],
                    ['name' => 'Bill Payments', 'status' => 'complete'],
                ],
            ],
            [
                'name' => 'Inventory',
                'percentage' => 90,
                'items' => [
                    ['name' => 'Products/Services CRUD', 'status' => 'complete'],
                    ['name' => 'Stock Movements', 'status' => 'complete'],
                    ['name' => 'Stock Adjustments', 'status' => 'complete'],
                    ['name' => 'Assets Management', 'status' => 'complete'],
                ],
            ],
            [
                'name' => 'HR/People',
                'percentage' => 95,
                'items' => [
                    ['name' => 'Team Members CRUD', 'status' => 'complete'],
                    ['name' => 'Departments', 'status' => 'complete'],
                    ['name' => 'Leave Types', 'status' => 'complete'],
                    ['name' => 'Leave Requests', 'status' => 'complete'],
                    ['name' => 'Payroll Runs', 'status' => 'complete'],
                ],
            ],
            [
                'name' => 'Compliance',
                'percentage' => 85,
                'items' => [
                    ['name' => 'Documents Management', 'status' => 'complete'],
                    ['name' => 'Licenses', 'status' => 'complete'],
                    ['name' => 'Certificates', 'status' => 'complete'],
                    ['name' => 'Tax Module', 'status' => 'placeholder', 'note' => 'Coming soon page'],
                ],
            ],
            [
                'name' => 'Decisions',
                'percentage' => 100,
                'items' => [
                    ['name' => 'OKRs', 'status' => 'complete'],
                    ['name' => 'Strategic Goals', 'status' => 'complete'],
                    ['name' => 'Business Valuations', 'status' => 'complete'],
                ],
            ],
            [
                'name' => 'Addy AI',
                'percentage' => 90,
                'items' => [
                    ['name' => 'Chat Interface', 'status' => 'complete'],
                    ['name' => 'Intent Parsing', 'status' => 'complete'],
                    ['name' => '4 Intelligence Agents', 'status' => 'complete'],
                    ['name' => 'Action Execution', 'status' => 'partial', 'note' => 'Only CreateTransaction fully implemented'],
                    ['name' => 'Predictions Engine', 'status' => 'complete'],
                    ['name' => 'Cultural Engine', 'status' => 'complete'],
                    ['name' => 'Caching Layer', 'status' => 'complete'],
                ],
            ],
            [
                'name' => 'Admin Panel',
                'percentage' => 95,
                'items' => [
                    ['name' => 'Dashboard', 'status' => 'complete'],
                    ['name' => 'Organizations Management', 'status' => 'complete'],
                    ['name' => 'Users Management', 'status' => 'complete'],
                    ['name' => 'Support Tickets', 'status' => 'complete'],
                    ['name' => 'System Settings', 'status' => 'complete'],
                    ['name' => 'Email Communication', 'status' => 'complete'],
                ],
            ],
            [
                'name' => 'Reports',
                'percentage' => 90,
                'items' => [
                    ['name' => 'Sales Report', 'status' => 'complete'],
                    ['name' => 'Revenue Report', 'status' => 'complete'],
                    ['name' => 'Expenses Report', 'status' => 'complete'],
                    ['name' => 'Profit & Loss', 'status' => 'complete'],
                    ['name' => 'Liabilities Report', 'status' => 'complete'],
                ],
            ],
        ];
    }

    /**
     * Get feature completeness
     */
    protected function getFeatureCompleteness(): array
    {
        return [
            [
                'name' => 'Authentication',
                'percentage' => 100,
                'items' => [
                    ['name' => 'Email/Password Login', 'status' => 'complete'],
                    ['name' => 'User Registration', 'status' => 'complete'],
                    ['name' => 'Google OAuth', 'status' => 'complete'],
                    ['name' => 'Password Reset', 'status' => 'complete'],
                    ['name' => 'Session Management', 'status' => 'complete'],
                ],
            ],
            [
                'name' => 'Multi-Tenancy',
                'percentage' => 100,
                'items' => [
                    ['name' => 'Organization Isolation', 'status' => 'complete'],
                    ['name' => 'Organization Switching', 'status' => 'complete'],
                    ['name' => 'Role-based Access', 'status' => 'complete'],
                    ['name' => 'Custom Organization Roles', 'status' => 'complete'],
                ],
            ],
            [
                'name' => 'Dashboard',
                'percentage' => 95,
                'items' => [
                    ['name' => 'Bento Grid Layout', 'status' => 'complete'],
                    ['name' => 'Draggable Cards', 'status' => 'complete'],
                    ['name' => 'Custom Card Selection', 'status' => 'complete'],
                    ['name' => 'Real-time Data', 'status' => 'complete'],
                    ['name' => 'Monthly Goal', 'status' => 'partial', 'note' => 'Hardcoded value'],
                ],
            ],
            [
                'name' => 'File Uploads',
                'percentage' => 100,
                'items' => [
                    ['name' => 'Receipt Uploads', 'status' => 'complete'],
                    ['name' => 'Document Uploads', 'status' => 'complete'],
                    ['name' => 'Google Drive Integration', 'status' => 'complete'],
                    ['name' => 'OCR Data Upload', 'status' => 'complete'],
                ],
            ],
            [
                'name' => 'Notifications',
                'percentage' => 95,
                'items' => [
                    ['name' => 'In-App Notifications', 'status' => 'complete'],
                    ['name' => 'Email Notifications', 'status' => 'partial', 'note' => 'Invoice email not implemented'],
                ],
            ],
            [
                'name' => 'Module System',
                'percentage' => 100,
                'items' => [
                    ['name' => 'Module Toggle', 'status' => 'complete'],
                    ['name' => 'Dynamic Navigation', 'status' => 'complete'],
                    ['name' => 'Module-specific Migrations', 'status' => 'complete'],
                ],
            ],
        ];
    }

    /**
     * Get documentation completeness
     */
    protected function getDocumentationCompleteness(): array
    {
        return [
            ['name' => 'README.md', 'status' => File::exists(base_path('README.md')) ? 'complete' : 'missing'],
            ['name' => 'PRODUCTION_CHECKLIST.md', 'status' => File::exists(base_path('PRODUCTION_CHECKLIST.md')) ? 'complete' : 'missing'],
            ['name' => 'DEPLOYMENT_STATUS.md', 'status' => File::exists(base_path('DEPLOYMENT_STATUS.md')) ? 'complete' : 'missing'],
            ['name' => 'LAUNCH_READINESS_REPORT.md', 'status' => File::exists(base_path('LAUNCH_READINESS_REPORT.md')) ? 'complete' : 'missing'],
            ['name' => 'MULTI_TENANCY_GUIDE.md', 'status' => File::exists(base_path('MULTI_TENANCY_GUIDE.md')) ? 'complete' : 'missing'],
            ['name' => 'API Documentation', 'status' => 'incomplete', 'note' => 'No OpenAPI/Swagger docs'],
            ['name' => 'User Guide', 'status' => 'incomplete', 'note' => 'No end-user documentation'],
        ];
    }

    /**
     * Get security completeness
     */
    protected function getSecurityCompleteness(): array
    {
        return [
            ['name' => 'Authentication System', 'status' => 'complete'],
            ['name' => 'Authorization (RBAC)', 'status' => 'complete'],
            ['name' => 'CSRF Protection', 'status' => 'complete'],
            ['name' => 'API Key Encryption', 'status' => 'complete'],
            ['name' => 'Multi-tenancy Isolation', 'status' => 'complete'],
            ['name' => 'Security Headers (Nginx)', 'status' => 'complete'],
            ['name' => 'SSL/HTTPS', 'status' => 'pending', 'note' => 'Awaiting DNS configuration'],
            ['name' => 'Rate Limiting', 'status' => 'partial', 'note' => 'Basic Laravel throttle'],
            ['name' => 'Input Validation', 'status' => 'complete'],
            ['name' => 'SQL Injection Protection', 'status' => 'complete', 'note' => 'Using Eloquent ORM'],
        ];
    }

    /**
     * Get deployment completeness
     */
    protected function getDeploymentCompleteness(): array
    {
        return [
            ['name' => 'Deploy Script', 'status' => File::exists(base_path('deploy.sh')) ? 'complete' : 'missing'],
            ['name' => 'Nginx Configuration', 'status' => File::exists(base_path('nginx-addy.conf')) ? 'complete' : 'missing'],
            ['name' => 'Supervisor Configuration', 'status' => File::exists(base_path('supervisor-addy.conf')) ? 'complete' : 'missing'],
            ['name' => 'Scheduled Tasks', 'status' => 'complete'],
            ['name' => 'Queue Workers', 'status' => 'complete'],
            ['name' => 'Database Migrations', 'status' => 'complete'],
            ['name' => 'Database Seeders', 'status' => 'complete'],
            ['name' => 'Environment Variables', 'status' => 'complete'],
            ['name' => 'Production Build', 'status' => 'complete'],
        ];
    }

    /**
     * Get last test run info
     */
    protected function getLastTestRun(): ?array
    {
        // Check for PHPUnit log
        $logFile = base_path('storage/logs/phpunit.log');
        if (File::exists($logFile)) {
            $modified = File::lastModified($logFile);
            return [
                'date' => date('Y-m-d H:i:s', $modified),
                'file' => 'phpunit.log',
            ];
        }
        return null;
    }
}


