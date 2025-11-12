<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardCardController;
use App\Http\Controllers\MoneyAccountController;
use App\Http\Controllers\MoneyMovementController;
use App\Http\Controllers\BudgetLineController;
use App\Http\Controllers\POSController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RegisterSessionController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockAdjustmentController;
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
use App\Http\Controllers\OKRController;
use App\Http\Controllers\StrategicGoalController;
use App\Http\Controllers\BusinessValuationController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect('/login');
});

// Lenco Webhooks (no auth required)
Route::post('/lenco/webhook', [\App\Http\Controllers\LencoPaymentController::class, 'webhook'])->name('lenco.webhook');
Route::post('/lenco/subscription-webhook', [\App\Http\Controllers\LencoSubscriptionWebhookController::class, 'handle'])->name('lenco.subscription-webhook');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    // Onboarding
    Route::get('/onboarding', [\App\Http\Controllers\OnboardingController::class, 'show'])->name('onboarding');
    Route::post('/onboarding/complete', [\App\Http\Controllers\OnboardingController::class, 'complete'])->name('onboarding.complete');
    
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
        
        // Support Tickets
        Route::resource('tickets', \App\Http\Controllers\Admin\AdminTicketController::class)->only(['index', 'show']);
        Route::post('/tickets/{ticket}/assign', [\App\Http\Controllers\Admin\AdminTicketController::class, 'assign'])->name('tickets.assign');
        Route::post('/tickets/{ticket}/status', [\App\Http\Controllers\Admin\AdminTicketController::class, 'updateStatus'])->name('tickets.update-status');
        Route::post('/tickets/{ticket}/messages', [\App\Http\Controllers\Admin\AdminTicketController::class, 'addMessage'])->name('tickets.add-message');
    });
    
    // Addy Insights API
    Route::prefix('api/addy')->group(function () {
        Route::get('/insights', [AddyInsightController::class, 'index']);
        Route::post('/insights/{insight}/dismiss', [AddyInsightController::class, 'dismiss']);
        Route::post('/insights/{insight}/complete', [AddyInsightController::class, 'complete']);
        
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
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/cards/reorder', [DashboardCardController::class, 'updateOrder'])->name('dashboard.cards.reorder');
    Route::post('/dashboard/cards/layout', [DashboardCardController::class, 'updateLayout'])->name('dashboard.cards.layout');
    Route::post('/dashboard/cards/{id}/toggle', [DashboardCardController::class, 'toggleVisibility'])->name('dashboard.cards.toggle');
    Route::post('/dashboard/cards/add', [DashboardCardController::class, 'addCard'])->name('dashboard.cards.add');
    Route::delete('/dashboard/cards/{id}', [DashboardCardController::class, 'removeCard'])->name('dashboard.cards.remove');
    
    // Placeholder routes for future features
    Route::get('/insights', function () {
        return Inertia::render('Placeholder', [
            'message' => 'Insights feature coming soon',
        ]);
    })->name('insights');
    
    Route::get('/ai-chat', function () {
        return Inertia::render('Placeholder', [
            'message' => 'AI Chat feature coming soon',
        ]);
    })->name('ai-chat');
    
    Route::get('/alerts', function () {
        return Inertia::render('Placeholder', [
            'message' => 'Alerts feature coming soon',
        ]);
    })->name('alerts');
    
    Route::get('/people/hr', function () {
        return Inertia::render('Placeholder', [
            'message' => 'HR feature coming soon',
        ]);
    })->name('people.hr');
    
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
    
    // Money Movements
    Route::resource('money/movements', MoneyMovementController::class)->names([
        'index' => 'money.movements.index',
        'create' => 'money.movements.create',
        'store' => 'money.movements.store',
        'show' => 'money.movements.show',
    ]);
    
    // Budget Lines
    Route::resource('money/budgets', BudgetLineController::class)->names([
        'index' => 'money.budgets.index',
        'create' => 'money.budgets.create',
        'store' => 'money.budgets.store',
        'edit' => 'money.budgets.edit',
        'update' => 'money.budgets.update',
        'destroy' => 'money.budgets.destroy',
    ]);
    
    // POS
    Route::get('/pos', [POSController::class, 'index'])->name('pos.index');
    Route::get('/pos/products/search', [POSController::class, 'searchProducts'])->name('pos.products.search');
    Route::get('/pos/products/barcode/{barcode}', [POSController::class, 'findByBarcode'])->name('pos.products.barcode');
    Route::get('/pos/customers/search', [POSController::class, 'searchCustomers'])->name('pos.customers.search');
    Route::post('/pos/sales', [SaleController::class, 'store'])->name('pos.sales.store');
    Route::get('/pos/sales/{sale}', [SaleController::class, 'show'])->name('pos.sales.show');
    Route::get('/sales/search', [SaleController::class, 'search'])->name('sales.search');
    
    // Sales Section
    Route::get('/sales', [App\Http\Controllers\SalesController::class, 'index'])->name('sales.index');
    
    // People Section
    Route::get('/people', [App\Http\Controllers\PeopleController::class, 'index'])->name('people.index');
    
    // Inventory Section
    Route::get('/inventory', [App\Http\Controllers\InventoryController::class, 'index'])->name('inventory.index');
    
    // Decisions Section
    Route::get('/decisions', [App\Http\Controllers\DecisionsController::class, 'index'])->name('decisions.index');
    
    // Compliance Section
    Route::get('/compliance', [App\Http\Controllers\ComplianceController::class, 'index'])->name('compliance.index');
    
    // Customers
    Route::resource('customers', CustomerController::class);
    Route::get('/customers/search', [CustomerController::class, 'search'])->name('customers.search');
    Route::post('/api/customers/quick-create', [CustomerController::class, 'quickCreate'])->name('api.customers.quick-create');
    
    // Quotes
    Route::resource('quotes', QuoteController::class);
    Route::post('/quotes/{quote}/convert', [QuoteController::class, 'convert'])->name('quotes.convert');
    Route::get('/quotes/{quote}/download', [QuoteController::class, 'downloadPdf'])->name('quotes.download');
    
    // Invoices
    Route::resource('invoices', InvoiceController::class);
    Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'downloadPdf'])->name('invoices.download');
    
    // Payments
    Route::resource('payments', PaymentController::class);
    Route::post('/payments/{payment}/allocate', [PaymentController::class, 'allocate'])->name('payments.allocate');
    
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
    
    // Register Sessions
    Route::get('/register-sessions', [RegisterSessionController::class, 'index'])->name('register.index');
    Route::post('/register-sessions/open', [RegisterSessionController::class, 'open'])->name('register.open');
    Route::post('/register-sessions/{session}/close', [RegisterSessionController::class, 'close'])->name('register.close');
    
    // Sale Returns
    Route::resource('sale-returns', SaleReturnController::class)->names([
        'index' => 'sale-returns.index',
        'create' => 'sale-returns.create',
        'store' => 'sale-returns.store',
        'show' => 'sale-returns.show',
    ]);
    
    // Products
    Route::resource('products', ProductController::class);
    Route::post('/api/products/quick-create', [ProductController::class, 'quickCreate'])->name('api.products.quick-create');
    
    // Stock Management
    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('/stock/movements', [StockMovementController::class, 'index'])->name('stock.movements.index');
    Route::get('/stock/movements/{movement}', [StockMovementController::class, 'show'])->name('stock.movements.show');
    Route::get('/stock/adjustments/create', [StockAdjustmentController::class, 'create'])->name('stock.adjustments.create');
    Route::post('/stock/adjustments', [StockAdjustmentController::class, 'store'])->name('stock.adjustments.store');
    
    // Departments
    Route::resource('departments', DepartmentController::class);
    
    // Team Members
    Route::resource('team', TeamMemberController::class)->names([
        'index' => 'team.index',
        'create' => 'team.create',
        'store' => 'team.store',
        'show' => 'team.show',
        'edit' => 'team.edit',
        'update' => 'team.update',
        'destroy' => 'team.destroy',
    ]);
    
    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/revenue', [ReportController::class, 'revenue'])->name('reports.revenue');
    Route::get('/reports/expenses', [ReportController::class, 'expenses'])->name('reports.expenses');
    Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit-loss');
    
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
    
    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    
    // Addy Settings
    Route::get('/settings/addy', [AddySettingsController::class, 'index'])->name('settings.addy');
    Route::post('/settings/addy', [AddySettingsController::class, 'update'])->name('settings.addy.update');
    
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
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
    
    // OKRs
    Route::resource('decisions/okrs', OKRController::class)->names([
        'index' => 'decisions.okrs.index',
        'create' => 'decisions.okrs.create',
        'store' => 'decisions.okrs.store',
        'show' => 'decisions.okrs.show',
        'edit' => 'decisions.okrs.edit',
        'update' => 'decisions.okrs.update',
        'destroy' => 'decisions.okrs.destroy',
    ]);
    Route::post('/decisions/okrs/{okr}/key-results', [OKRController::class, 'addKeyResult'])->name('decisions.okrs.key-results.store');
    Route::put('/decisions/okrs/{okr}/key-results/{keyResult}', [OKRController::class, 'updateKeyResult'])->name('decisions.okrs.key-results.update');
    
    // Strategic Goals
    Route::resource('decisions/goals', StrategicGoalController::class)->names([
        'index' => 'decisions.goals.index',
        'create' => 'decisions.goals.create',
        'store' => 'decisions.goals.store',
        'show' => 'decisions.goals.show',
        'edit' => 'decisions.goals.edit',
        'update' => 'decisions.goals.update',
        'destroy' => 'decisions.goals.destroy',
    ]);
    Route::post('/decisions/goals/{goal}/milestones', [StrategicGoalController::class, 'addMilestone'])->name('decisions.goals.milestones.store');
    
    // Business Valuations
    Route::resource('decisions/valuation', BusinessValuationController::class)->names([
        'index' => 'decisions.valuation.index',
        'create' => 'decisions.valuation.create',
        'store' => 'decisions.valuation.store',
        'show' => 'decisions.valuation.show',
        'edit' => 'decisions.valuation.edit',
        'update' => 'decisions.valuation.update',
        'destroy' => 'decisions.valuation.destroy',
    ]);
    
    // Projects
    Route::resource('projects', ProjectController::class);
});
