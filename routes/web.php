<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardCardController;
use App\Http\Controllers\MoneyAccountController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\TeamMemberController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\PayrollRunController;
use App\Http\Controllers\PayrollItemController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Api\AddyInsightController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Admin\SystemSettingsController;
use App\Http\Controllers\AddyChatController;
use App\Http\Controllers\AddyActionController;
use App\Http\Controllers\Settings\AddySettingsController;
use App\Http\Controllers\CommissionRuleController;
use App\Http\Controllers\CommissionEarningController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\CertificateController;

use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

// Legal Pages (Public)
Route::get('/privacy-policy', function () {
    return Inertia::render('Legal/PrivacyPolicy');
})->name('privacy-policy');

Route::get('/terms-of-service', function () {
    return Inertia::render('Legal/TermsOfService');
})->name('terms-of-service');

Route::get('/enterprise', function () {
    return Inertia::render('Enterprise');
})->name('enterprise');

// Lenco Webhooks (no auth required)
Route::post('/lenco/webhook', [\App\Http\Controllers\LencoPaymentController::class, 'webhook'])->name('lenco.webhook');
Route::post('/lenco/subscription-webhook', [\App\Http\Controllers\LencoSubscriptionWebhookController::class, 'handle'])->name('lenco.subscription-webhook');

// Guest routes (not logged in) - Only Penda Cloud SSO
Route::middleware('guest')->group(function () {
    // Login page - auto-redirects to Penda Cloud
    Route::get('/login', function () {
        return Inertia::render('Auth/Login');
    })->name('login');
    
    // Penda Cloud SSO - Only authentication method
    Route::get('/auth/penda', [\App\Http\Controllers\Auth\PendaSSOController::class, 'redirect'])->name('penda.login');
});

// Penda SSO Callback (must be accessible for redirect)
Route::get('/auth/penda/callback', [\App\Http\Controllers\Auth\PendaSSOController::class, 'callback'])->name('penda.callback');

// Google Drive OAuth Callback (must be accessible without auth for Google redirect)
Route::get('/auth/google/callback', [\App\Http\Controllers\GoogleAuthController::class, 'callback'])->name('google.callback');

Route::middleware('auth')->group(function () {
    // Organization switching
    Route::post('/organizations/{organization}/switch', [\App\Http\Controllers\OrganizationSwitchController::class, 'switch'])->name('organizations.switch');
    Route::get('/api/organizations', [\App\Http\Controllers\OrganizationSwitchController::class, 'index'])->name('organizations.index');
    Route::post('/api/organizations/create', [\App\Http\Controllers\OrganizationSwitchController::class, 'create'])->name('organizations.create');
    
    // Onboarding is now handled by Penda Cloud
    // Redirect any onboarding requests to Penda Cloud
    Route::get('/onboarding', function () {
        $pendaCloudUrl = config('services.penda_sso.base_url', 'https://penda.cloud');
        return redirect($pendaCloudUrl . '/onboarding/step-1');
    })->name('onboarding');
    
    Route::prefix('api/onboarding')->group(function () {
        Route::any('/{any}', function () {
            $pendaCloudUrl = config('services.penda_sso.base_url', 'https://penda.cloud');
            return redirect($pendaCloudUrl . '/onboarding/step-1');
        })->where('any', '.*');
    });
    
    // Super Admin only routes - Platform management
    Route::prefix('admin')->middleware(['admin'])->name('admin.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])->name('dashboard');
        
        // Profile Management
        Route::get('/profile', [\App\Http\Controllers\Admin\AdminProfileController::class, 'show'])->name('profile');
        Route::put('/profile', [\App\Http\Controllers\Admin\AdminProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [\App\Http\Controllers\Admin\AdminProfileController::class, 'updatePassword'])->name('profile.password');
        
        // System Settings (AI, Platform Configuration)
        Route::get('/system-settings', [SystemSettingsController::class, 'index'])->name('system-settings');
        Route::post('/system-settings', [SystemSettingsController::class, 'update'])->name('system-settings.update');
        Route::post('/system-settings/test', [SystemSettingsController::class, 'testConnection'])->name('system-settings.test');
        
        // Platform Settings (Global Settings)
        Route::get('/settings', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'update'])->name('settings.update');
        
        // Organization Management
        Route::resource('organizations', \App\Http\Controllers\Admin\AdminOrganizationController::class);
        Route::post('/organizations/{organization}/suspend', [\App\Http\Controllers\Admin\AdminOrganizationController::class, 'suspend'])->name('organizations.suspend');
        Route::post('/organizations/{organization}/unsuspend', [\App\Http\Controllers\Admin\AdminOrganizationController::class, 'unsuspend'])->name('organizations.unsuspend');
        
        // User Management
        Route::resource('users', \App\Http\Controllers\Admin\AdminUserController::class);
        Route::post('/users/{user}/toggle-super-admin', [\App\Http\Controllers\Admin\AdminUserController::class, 'toggleSuperAdmin'])->name('users.toggle-super-admin');
        Route::post('/users/{user}/toggle-active', [\App\Http\Controllers\Admin\AdminUserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::post('/users/{user}/change-password', [\App\Http\Controllers\Admin\AdminUserController::class, 'changePassword'])->name('users.change-password');
        Route::post('/users/{user}/send-password-reset', [\App\Http\Controllers\Admin\AdminUserController::class, 'sendPasswordReset'])->name('users.send-password-reset');
        Route::post('/users/{user}/change-organization-role', [\App\Http\Controllers\Admin\AdminUserController::class, 'changeOrganizationRole'])->name('users.change-organization-role');
        
        // Role Management
        Route::resource('roles', \App\Http\Controllers\OrganizationRoleController::class);
        
        // Support Tickets
        Route::resource('tickets', \App\Http\Controllers\Admin\AdminTicketController::class)->only(['index', 'show', 'create', 'store']);
        Route::post('/tickets/{ticket}/assign', [\App\Http\Controllers\Admin\AdminTicketController::class, 'assign'])->name('tickets.assign');
        Route::post('/tickets/{ticket}/status', [\App\Http\Controllers\Admin\AdminTicketController::class, 'updateStatus'])->name('tickets.update-status');
        Route::post('/tickets/{ticket}/messages', [\App\Http\Controllers\Admin\AdminTicketController::class, 'addMessage'])->name('tickets.add-message');
        
        // Communication
        Route::prefix('communication')->name('communication.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminCommunicationController::class, 'index'])->name('index');
            Route::get('/templates', [\App\Http\Controllers\Admin\AdminCommunicationController::class, 'templates'])->name('templates');
            Route::get('/templates/{id}/edit', [\App\Http\Controllers\Admin\AdminCommunicationController::class, 'editTemplate'])->name('templates.edit');
            Route::put('/templates/{id}', [\App\Http\Controllers\Admin\AdminCommunicationController::class, 'updateTemplate'])->name('templates.update');
            Route::post('/templates/{id}/preview', [\App\Http\Controllers\Admin\AdminCommunicationController::class, 'previewTemplate'])->name('templates.preview');
            Route::post('/upload-image', [\App\Http\Controllers\Admin\AdminCommunicationController::class, 'uploadImage'])->name('upload-image');
            Route::get('/send', [\App\Http\Controllers\Admin\AdminCommunicationController::class, 'sendEmail'])->name('send');
            Route::post('/send', [\App\Http\Controllers\Admin\AdminCommunicationController::class, 'sendBulkEmail'])->name('send.post');
            Route::get('/logs', [\App\Http\Controllers\Admin\AdminCommunicationController::class, 'logs'])->name('logs');
        });
        
        // Testing & Completeness
        Route::get('/testing', [\App\Http\Controllers\Admin\AdminTestingController::class, 'index'])->name('testing');
        Route::post('/testing/run', [\App\Http\Controllers\Admin\AdminTestingController::class, 'runTests'])->name('testing.run');
    });
    
    // Addy Insights API
    Route::prefix('api/addy')->group(function () {
        Route::get('/insights', [AddyInsightController::class, 'index']);
        Route::post('/insights/refresh', [AddyInsightController::class, 'refresh']);
        Route::post('/insights/{insight}/dismiss', [AddyInsightController::class, 'dismiss'])->where('insight', '[0-9]+');
        Route::post('/insights/{insight}/complete', [AddyInsightController::class, 'complete'])->where('insight', '[0-9]+');
        
        // Addy Chat
        Route::post('/chat', [AddyChatController::class, 'sendMessage']);
        Route::get('/chat/history', [AddyChatController::class, 'getHistory']);
        Route::delete('/chat/history', [AddyChatController::class, 'clearHistory']);
        
        // Attachments (for entities like Customer, Invoice, Quote, etc.)
        Route::prefix('attachments')->group(function () {
            Route::post('/', [\App\Http\Controllers\AttachmentController::class, 'store']);
            Route::get('/', [\App\Http\Controllers\AttachmentController::class, 'index']);
            Route::delete('/{id}', [\App\Http\Controllers\AttachmentController::class, 'destroy']);
            Route::get('/{id}/download', [\App\Http\Controllers\AttachmentController::class, 'download']);
        });
        
        // Addy Actions
        Route::post('/actions/{action}/confirm', [AddyActionController::class, 'confirm'])->name('addy.actions.confirm');
        Route::post('/actions/{action}/cancel', [AddyActionController::class, 'cancel'])->name('addy.actions.cancel');
        Route::post('/actions/{action}/rate', [AddyActionController::class, 'rate'])->name('addy.actions.rate');
        Route::get('/actions/history', [AddyActionController::class, 'history'])->name('addy.actions.history');
        Route::get('/actions/suggestions', [AddyActionController::class, 'suggestions'])->name('addy.actions.suggestions');
    });
    Route::post('/logout', [\App\Http\Controllers\Auth\PendaSSOController::class, 'logout'])->name('logout');
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Dashboard Card Data API (with caching)
    Route::get('/api/dashboard/card-data/{cardId}', [\App\Http\Controllers\DashboardCardDataController::class, 'getCardData'])->name('dashboard.card-data');
    
    Route::post('/dashboard/cards/reorder', [DashboardCardController::class, 'updateOrder'])->name('dashboard.cards.reorder');
    Route::post('/dashboard/cards/layout', [DashboardCardController::class, 'updateLayout'])->name('dashboard.cards.layout');
    Route::post('/dashboard/cards/{id}/toggle', [DashboardCardController::class, 'toggleVisibility'])->name('dashboard.cards.toggle');
    Route::post('/dashboard/cards/add', [DashboardCardController::class, 'addCard'])->name('dashboard.cards.add');
    Route::delete('/dashboard/cards/{id}', [DashboardCardController::class, 'removeCard'])->name('dashboard.cards.remove');
    
    // HR module routes are handled by module system
    
    Route::get('/compliance/tax', function () {
        return Inertia::render('Placeholder', [
            'message' => 'Tax feature coming soon',
        ]);
    })->name('compliance.tax');
    
    // Money Section
    Route::get('/money', [App\Http\Controllers\MoneyController::class, 'index'])->name('money.index');
    
    // Money Accounts
    Route::resource('money/accounts', MoneyAccountController::class)->names([
        'index' => 'money.accounts.index',
        'create' => 'money.accounts.create',
        'store' => 'money.accounts.store',
        'show' => 'money.accounts.show',
        'edit' => 'money.accounts.edit',
        'update' => 'money.accounts.update',
        'destroy' => 'money.accounts.destroy',
    ]);
    
    // Transactions (formerly Money Movements)
    Route::resource('transactions', TransactionController::class)->only(['index', 'create', 'store', 'show', 'update']);
    Route::get('/transactions/upload', [TransactionController::class, 'upload'])->name('transactions.upload');
    Route::post('/transactions/upload', [TransactionController::class, 'processUpload'])->name('transactions.process-upload');
    Route::get('/transactions/verify', [TransactionController::class, 'verify'])->name('transactions.verify');
    Route::post('/transactions/{id}/match-invoice', [TransactionController::class, 'matchInvoice'])->name('transactions.match-invoice');
    Route::post('/transactions/{id}/verify', [TransactionController::class, 'markAsVerified'])->name('transactions.mark-verified');
    
    // Central Upload API
    Route::post('/api/upload', [\App\Http\Controllers\UploadController::class, 'upload'])->name('upload');
    Route::post('/transactions/{id}/upload-receipt', [TransactionController::class, 'uploadReceipt'])->name('transactions.upload-receipt');
    Route::post('/transactions/bulk-delete', [TransactionController::class, 'bulkDelete'])->name('transactions.bulk-delete');
    
    // Income
    Route::resource('income', IncomeController::class);
    
    // Money Expenses (all expense transactions, not just bills)
    Route::prefix('money')->name('money.')->group(function () {
        Route::resource('expenses', ExpenseController::class)->names([
            'index' => 'money.expenses.index',
            'create' => 'money.expenses.create',
            'store' => 'money.expenses.store',
            'show' => 'money.expenses.show',
            'edit' => 'money.expenses.edit',
            'update' => 'money.expenses.update',
            'destroy' => 'money.expenses.destroy',
        ]);
    });
    
    // Budgets routes are now handled by the Budgets module
    
    // POS routes are now handled by Retail module
    
    // Sales Section
    Route::get('/sales', [App\Http\Controllers\SalesController::class, 'index'])->name('sales.index');
    
    // Expenses Section (formerly People)
    Route::get('/expenses', [App\Http\Controllers\ExpensesController::class, 'index'])->name('expenses.index');
    
    // Customer & Vendor Management Routes
    require __DIR__.'/customer_vendor.php';
    
    // Inventory Section
    Route::get('/inventory', [App\Http\Controllers\InventoryController::class, 'index'])->name('inventory.index');
    

    // Compliance Section
    Route::get('/compliance', [App\Http\Controllers\ComplianceController::class, 'index'])->name('compliance.index');
    
    // Customers routes are now in customer_vendor.php (Sales section)
    // Keeping search and quick-create API routes for backward compatibility
    Route::get('/customers/search', [CustomerController::class, 'search'])->name('customers.search');
    Route::post('/api/customers/quick-create', [CustomerController::class, 'quickCreate'])->name('api.customers.quick-create');
    
    // Quotes - redirect to quotations (merged)
    Route::get('/quotes', function () {
        return redirect()->route('quotations.index');
    });
    Route::get('/quotes/{any}', function ($any) {
        return redirect()->route('quotations.show', $any);
    })->where('any', '.*');
    
    // Invoices
    Route::resource('invoices', InvoiceController::class);
    Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'downloadPdf'])->name('invoices.download');
    
    // Payments
    Route::resource('payments', PaymentController::class);
    Route::get('/payments/{payment}/allocate', [PaymentController::class, 'showAllocate'])->name('payments.allocate.show');
    Route::post('/payments/{payment}/allocate', [PaymentController::class, 'allocate'])->name('payments.allocate');
    
    // Receipts
    Route::get('/receipts/{receipt}', [\App\Http\Controllers\ReceiptController::class, 'show'])->name('receipts.show');
    Route::get('/receipts/{receipt}/download', [\App\Http\Controllers\ReceiptController::class, 'downloadPdf'])->name('receipts.download');
    
    // Lenco Payment Gateway
    Route::prefix('lenco')->name('lenco.')->group(function () {
        Route::post('/initialize', [\App\Http\Controllers\LencoPaymentController::class, 'initialize'])->name('initialize');
        Route::post('/verify', [\App\Http\Controllers\LencoPaymentController::class, 'verify'])->name('verify');
        Route::get('/callback', [\App\Http\Controllers\LencoPaymentController::class, 'callback'])->name('callback');
    });
    
    // Subscriptions
    Route::get('/subscriptions', [\App\Http\Controllers\SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('/subscriptions/subscribe', [\App\Http\Controllers\SubscriptionController::class, 'subscribe'])->name('subscriptions.subscribe');
    Route::post('/subscriptions/cancel', [\App\Http\Controllers\SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    Route::get('/subscriptions/callback', [\App\Http\Controllers\SubscriptionController::class, 'callback'])->name('subscriptions.callback');
    
    // Register Sessions and Sale Returns routes are now handled by Retail module
    
    // Products
    Route::resource('products', ProductController::class);
    Route::post('/api/products/quick-create', [ProductController::class, 'quickCreate'])->name('api.products.quick-create');
    
    // Stock Adjustments (connected to products)
    Route::get('/products/stock-adjustment', [\App\Http\Controllers\StockAdjustmentController::class, 'create'])->name('products.stock-adjustment');
    Route::post('/products/stock-adjustment', [\App\Http\Controllers\StockAdjustmentController::class, 'store'])->name('products.stock-adjustment.store');
    
    // Assets (Internal Assets)
    Route::resource('assets', AssetController::class);
    
    
    // Departments
    Route::resource('departments', DepartmentController::class);
    
    // Team Members (moved to Settings) - Views go through SettingsController to stay in settings context
    Route::prefix('settings')->name('settings.')->group(function () {
        // Views - handled by SettingsController to keep within settings page
        Route::get('/team', [SettingsController::class, 'index'])->name('team.index');
        Route::get('/team/create', [SettingsController::class, 'index'])->name('team.create');
        Route::get('/team/{id}', [SettingsController::class, 'index'])->name('team.show');
        Route::get('/team/{id}/edit', [SettingsController::class, 'index'])->name('team.edit');
        
        // Actions - handled by TeamMemberController
        Route::post('/team', [TeamMemberController::class, 'store'])->name('team.store');
        Route::put('/team/{id}', [TeamMemberController::class, 'update'])->name('team.update');
        Route::delete('/team/{id}', [TeamMemberController::class, 'destroy'])->name('team.destroy');
        Route::post('/team/{id}/upload-document', [TeamMemberController::class, 'uploadDocument'])->name('team.upload-document');
        Route::post('/team/{id}/grant-access', [TeamMemberController::class, 'grantAccess'])->name('team.grant-access');
        Route::post('/team/{id}/update-user-role', [TeamMemberController::class, 'updateUserRole'])->name('team.update-user-role');
    });
    
    // Legacy team routes - redirect to settings
    Route::get('/team', function () {
        return redirect()->route('settings.team.index');
    });
    Route::get('/team/{any}', function ($any) {
        return redirect()->route('settings.team.show', $any);
    })->where('any', '.*');
    
    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/revenue', [ReportController::class, 'revenue'])->name('reports.revenue');
    Route::get('/reports/expenses', [ReportController::class, 'expenses'])->name('reports.expenses');
    Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit-loss');
    Route::get('/reports/liabilities', [ReportController::class, 'liabilities'])->name('reports.liabilities');
    Route::get('/reports/projected-income', [ReportController::class, 'projectedIncome'])->name('reports.projected-income');
    
    // Leave Types
    Route::resource('leave/types', LeaveTypeController::class)->names([
        'index' => 'leave.types.index',
        'create' => 'leave.types.create',
        'store' => 'leave.types.store',
        'edit' => 'leave.types.edit',
        'update' => 'leave.types.update',
        'destroy' => 'leave.types.destroy',
    ]);
    
    // Leave Requests
    Route::resource('leave/requests', LeaveRequestController::class)->names([
        'index' => 'leave.requests.index',
        'create' => 'leave.requests.create',
        'store' => 'leave.requests.store',
        'show' => 'leave.requests.show',
    ]);
    Route::post('/leave/requests/{id}/approve', [LeaveRequestController::class, 'approve'])->name('leave.requests.approve');
    Route::post('/leave/requests/{id}/reject', [LeaveRequestController::class, 'reject'])->name('leave.requests.reject');
    
    // Payroll Runs
    Route::resource('payroll/runs', PayrollRunController::class)->names([
        'index' => 'payroll.runs.index',
        'create' => 'payroll.runs.create',
        'store' => 'payroll.runs.store',
        'show' => 'payroll.runs.show',
    ]);
    Route::post('/payroll/runs/{id}/process', [PayrollRunController::class, 'process'])->name('payroll.runs.process');
    
    // Payroll Items
    Route::get('/payroll/items/{id}', [PayrollItemController::class, 'show'])->name('payroll.items.show');
    
    // Activity Logs
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    
    // Unified Settings - All settings views go through SettingsController
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/logo', [SettingsController::class, 'updateLogo'])->name('settings.update-logo');
    Route::post('/settings/drive-preference', [SettingsController::class, 'updateDrivePreference'])->name('settings.drive-preference');
    Route::post('/settings/disconnect-drive', [SettingsController::class, 'disconnectDrive'])->name('settings.disconnect-drive');
    
    // Settings sub-pages - all render unified Settings page
    Route::get('/settings/addy', [SettingsController::class, 'index'])->name('settings.addy');
    Route::post('/settings/addy', [AddySettingsController::class, 'update'])->name('settings.addy.update');
    Route::get('/settings/invoices', [SettingsController::class, 'index'])->name('settings.invoices');
    Route::post('/settings/invoices', [\App\Http\Controllers\Settings\InvoiceSettingsController::class, 'update'])->name('settings.invoices.update');
    Route::get('/settings/modules', [SettingsController::class, 'index'])->name('settings.modules');
    
    // Module toggle action
    Route::post('/modules/{module}/toggle', [\App\Http\Controllers\ModuleController::class, 'toggle'])->name('modules.toggle');
    Route::get('/api/modules/all', [\App\Http\Controllers\ModuleController::class, 'getAllModules'])->name('api.modules.all');
    Route::get('/api/modules/navigation', [\App\Http\Controllers\ModuleController::class, 'getModulesForNavigation'])->name('api.modules.navigation');
    
    // Tax Rates API routes
    Route::prefix('api/tax-rates')->name('tax-rates.')->middleware(['auth', 'verified'])->group(function () {
        Route::get('/', [\App\Modules\Tax\Http\Controllers\TaxRateController::class, 'index'])->name('index');
        Route::post('/', [\App\Modules\Tax\Http\Controllers\TaxRateController::class, 'store'])->name('store');
        Route::put('/{taxRate}', [\App\Modules\Tax\Http\Controllers\TaxRateController::class, 'update'])->name('update');
        Route::delete('/{taxRate}', [\App\Modules\Tax\Http\Controllers\TaxRateController::class, 'destroy'])->name('destroy');
    });
    
    // Support Tickets
    Route::prefix('support/tickets')->name('support.tickets.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SupportTicketController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\SupportTicketController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\SupportTicketController::class, 'store'])->name('store');
        Route::get('/{ticket}', [\App\Http\Controllers\SupportTicketController::class, 'show'])->name('show');
        Route::post('/{ticket}/messages', [\App\Http\Controllers\SupportTicketController::class, 'addMessage'])->name('messages.store');
    });
    
    // Data Upload (Agent-based background processing)
    Route::prefix('data-upload')->name('data-upload.')->group(function () {
        Route::get('/', [\App\Http\Controllers\AgentDataUploadController::class, 'index'])->name('index');
        Route::post('/upload', [\App\Http\Controllers\AgentDataUploadController::class, 'upload'])->name('upload');
        Route::get('/status/{id}', [\App\Http\Controllers\AgentDataUploadController::class, 'status'])->name('status');
        Route::post('/import-reviewed/{id}', [\App\Http\Controllers\AgentDataUploadController::class, 'importReviewed'])->name('import-reviewed');
        Route::post('/cancel/{id}', [\App\Http\Controllers\AgentDataUploadController::class, 'cancel'])->name('cancel');
    });
    
    // Google Drive Authentication
    Route::get('/auth/google', [\App\Http\Controllers\GoogleAuthController::class, 'redirect'])->name('google.auth');
    
    // Category Search API
    Route::get('/api/categories/search', [\App\Http\Controllers\CategoryController::class, 'search'])->name('api.categories.search');
    
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/api/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    
    // Commission Rules
    Route::resource('commissions/rules', CommissionRuleController::class)->names([
        'index' => 'commissions.rules.index',
        'create' => 'commissions.rules.create',
        'store' => 'commissions.rules.store',
        'edit' => 'commissions.rules.edit',
        'update' => 'commissions.rules.update',
        'destroy' => 'commissions.rules.destroy',
    ]);
    
    // Commission Earnings
    Route::get('/commissions/earnings', [CommissionEarningController::class, 'index'])->name('commissions.earnings.index');
    
    // Documents
    Route::resource('compliance/documents', DocumentController::class)->names([
        'index' => 'compliance.documents.index',
        'create' => 'compliance.documents.create',
        'store' => 'compliance.documents.store',
        'show' => 'compliance.documents.show',
        'edit' => 'compliance.documents.edit',
        'update' => 'compliance.documents.update',
        'destroy' => 'compliance.documents.destroy',
    ]);
    Route::post('compliance/documents/{id}/assign', [DocumentController::class, 'assignToTeamMembers'])->name('compliance.documents.assign');
    Route::delete('compliance/documents/{id}/unassign/{teamMemberId}', [DocumentController::class, 'unassignFromTeamMember'])->name('compliance.documents.unassign');
    
    // Licenses
    Route::resource('compliance/licenses', LicenseController::class)->names([
        'index' => 'compliance.licenses.index',
        'create' => 'compliance.licenses.create',
        'store' => 'compliance.licenses.store',
        'edit' => 'compliance.licenses.edit',
        'update' => 'compliance.licenses.update',
        'destroy' => 'compliance.licenses.destroy',
    ]);
    
    // Certificates
    Route::resource('compliance/certificates', CertificateController::class)->names([
        'index' => 'compliance.certificates.index',
        'create' => 'compliance.certificates.create',
        'store' => 'compliance.certificates.store',
        'edit' => 'compliance.certificates.edit',
        'update' => 'compliance.certificates.update',
        'destroy' => 'compliance.certificates.destroy',
    ]);
    

    // Projects
    Route::resource('projects', ProjectController::class);
    
    // NeuroCore Chat (Beta) - Separate from main Addy chat
    Route::get('/neuro/chat', function () {
        return Inertia::render('Neuro/Chat');
    })->name('neuro.chat');
});

// Customer & Vendor Dashboard Route
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/customer-vendor', function () {
        return Inertia\Inertia::render('CustomerVendor/Dashboard');
    })->name('customer-vendor.dashboard');
});

// Card Stack Routes (Swipeable cards for tasks, notifications, etc.)
require __DIR__.'/card-stack-routes.php';
