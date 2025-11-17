<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use App\Models\MoneyAccount;
use App\Models\Customer;
use App\Models\GoodsAndService;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\MoneyMovement;
use App\Models\BudgetLine;
use App\Models\StockMovement;
use App\Models\TeamMember;
use App\Models\Department;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Models\Project;
use App\Models\PayrollRun;
use App\Models\PayrollItem;
use App\Models\OKR;
use App\Models\KeyResult;
use App\Models\Document;
use App\Models\AddyState;
use App\Models\AddyInsight;
use App\Models\AddyChatMessage;
use App\Models\AddyAction;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organization = Organization::where('slug', 'test-company')->first();
        
        if (!$organization) {
            $this->command->error('Organization not found. Please run TestUserSeeder first.');
            return;
        }

        $user = User::where('organization_id', $organization->id)->first();
        
        if (!$user) {
            $this->command->error('User not found. Please run TestUserSeeder first.');
            return;
        }

        // Set authenticated user for model events
        auth()->login($user);

        // Create TeamMember for the user (required for sales)
        $teamMember = TeamMember::firstOrCreate(
            ['user_id' => $user->id],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'first_name' => explode(' ', $user->name)[0] ?? 'Test',
                'last_name' => explode(' ', $user->name)[1] ?? 'User',
                'email' => $user->email,
                'employee_number' => 'EMP001',
                'job_title' => 'Manager',
                'hire_date' => Carbon::now()->subYear(),
                'is_active' => true,
            ]
        );

        $this->command->info('Seeding test data for the past month...');

        // Create Money Accounts
        $accounts = $this->createMoneyAccounts($organization, $user);
        $this->command->info('✓ Created ' . count($accounts) . ' money accounts');

        // Create Customers
        $customers = $this->createCustomers($organization);
        $this->command->info('✓ Created ' . count($customers) . ' customers');

        // Create Products
        $products = $this->createProducts($organization);
        $this->command->info('✓ Created ' . count($products) . ' products');

        // Create initial stock
        $this->createInitialStock($organization, $products, $user);
        $this->command->info('✓ Created initial stock movements');

        // Create Sales over the past month
        $sales = $this->createSales($organization, $customers, $products, $accounts, $teamMember);
        $this->command->info('✓ Created ' . count($sales) . ' sales');

        // Create Invoices
        $invoices = $this->createInvoices($organization, $customers, $products);
        $this->command->info('✓ Created ' . count($invoices) . ' invoices');

        // Create Payments
        $this->createPayments($organization, $invoices, $accounts, $user);
        $this->command->info('✓ Created payments');

        // Create Quotes
        $quotes = $this->createQuotes($organization, $customers, $products);
        $this->command->info('✓ Created ' . count($quotes) . ' quotes');

        // Create Money Movements (expenses)
        $this->createExpenses($organization, $accounts, $user);
        $this->command->info('✓ Created expense movements');

        // Create Budget Lines
        $this->createBudgets($organization);
        $this->command->info('✓ Created budget lines');

        // Create Departments
        $departments = $this->createDepartments($organization, $teamMember);
        $this->command->info('✓ Created ' . count($departments) . ' departments');

        // Create additional Team Members
        $additionalTeamMembers = $this->createTeamMembers($organization, $departments);
        $teamMembers = array_merge([$teamMember], $additionalTeamMembers);
        $this->command->info('✓ Created ' . count($additionalTeamMembers) . ' additional team members');

        // Create Leave Types
        $leaveTypes = $this->createLeaveTypes($organization);
        $this->command->info('✓ Created ' . count($leaveTypes) . ' leave types');

        // Create Leave Requests
        $leaveRequests = $this->createLeaveRequests($organization, $teamMembers, $leaveTypes, $user);
        $this->command->info('✓ Created ' . count($leaveRequests) . ' leave requests');

        // Create Projects
        $projects = $this->createProjects($organization, $user, $teamMembers);
        $this->command->info('✓ Created ' . count($projects) . ' projects');

        // Create Payroll Runs
        $payrollRuns = $this->createPayrollRuns($organization, $teamMembers, $user);
        $this->command->info('✓ Created ' . count($payrollRuns) . ' payroll runs');

        // Create OKRs
        $okrs = $this->createOKRs($organization, $user, $teamMembers);
        $this->command->info('✓ Created ' . count($okrs) . ' OKRs');

        // Create Documents
        $documents = $this->createDocuments($organization, $user);
        $this->command->info('✓ Created ' . count($documents) . ' documents');

        // Create Addy State and Insights
        $addyState = $this->createAddyState($organization);
        $this->createAddyInsights($organization, $addyState);
        $this->command->info('✓ Created Addy state and insights');

        // Create Addy Chat Messages
        $this->createAddyChatMessages($organization, $user);
        $this->command->info('✓ Created Addy chat messages');

        // Create Addy Actions
        $this->createAddyActions($organization, $user);
        $this->command->info('✓ Created Addy actions');

        // Create Support Tickets
        $supportTickets = $this->createSupportTickets($organization, $user);
        $this->command->info('✓ Created ' . count($supportTickets) . ' support tickets');

        $this->command->info('✅ Test data seeding completed!');
    }

    private function createMoneyAccounts($organization, $user): array
    {
        $accounts = [];
        
        $accountTypes = [
            ['name' => 'Main Cash Account', 'type' => 'cash', 'opening_balance' => 50000],
            ['name' => 'Business Bank Account', 'type' => 'bank', 'opening_balance' => 150000, 'bank_name' => 'Zanaco', 'account_number' => '1234567890'],
            ['name' => 'Petty Cash', 'type' => 'cash', 'opening_balance' => 5000],
        ];

        foreach ($accountTypes as $accountData) {
            $account = MoneyAccount::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'name' => $accountData['name'],
                'type' => $accountData['type'],
                'account_number' => $accountData['account_number'] ?? null,
                'bank_name' => $accountData['bank_name'] ?? null,
                'currency' => 'ZMW',
                'opening_balance' => $accountData['opening_balance'],
                'current_balance' => $accountData['opening_balance'],
                'is_active' => true,
            ]);
            $accounts[] = $account;
        }

        return $accounts;
    }

    private function createCustomers($organization): array
    {
        $customers = [];
        $names = [
            'John Mwansa', 'Sarah Banda', 'Michael Phiri', 'Grace Tembo', 'David Mulenga',
            'Ruth Mwanza', 'Peter Chanda', 'Mary Ngoma', 'James Mbewe', 'Esther Kunda',
            'ABC Trading Ltd', 'XYZ Services', 'Global Solutions', 'Tech Innovations', 'Prime Retail'
        ];

        foreach ($names as $name) {
            $isCompany = str_contains($name, 'Ltd') || str_contains($name, 'Services') || str_contains($name, 'Solutions') || str_contains($name, 'Innovations') || str_contains($name, 'Retail');
            
            $customers[] = Customer::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'name' => $name,
                'email' => strtolower(str_replace(' ', '.', $name)) . '@example.com',
                'phone' => '097' . rand(1000000, 9999999),
                'company_name' => $isCompany ? $name : null,
                'address' => rand(1, 100) . ' Main Street, Lusaka',
                'tax_id' => $isCompany ? 'TAX' . rand(100000, 999999) : null,
            ]);
        }

        return $customers;
    }

    private function createProducts($organization): array
    {
        $products = [];
        $productData = [
            ['name' => 'Laptop Computer', 'type' => 'product', 'cost' => 8000, 'price' => 12000, 'stock' => 50, 'category' => 'Electronics'],
            ['name' => 'Wireless Mouse', 'type' => 'product', 'cost' => 50, 'price' => 150, 'stock' => 200, 'category' => 'Electronics'],
            ['name' => 'Keyboard', 'type' => 'product', 'cost' => 200, 'price' => 450, 'stock' => 150, 'category' => 'Electronics'],
            ['name' => 'Monitor 24"', 'type' => 'product', 'cost' => 1500, 'price' => 2500, 'stock' => 75, 'category' => 'Electronics'],
            ['name' => 'USB Cable', 'type' => 'product', 'cost' => 30, 'price' => 80, 'stock' => 500, 'category' => 'Accessories'],
            ['name' => 'Web Development', 'type' => 'service', 'cost' => 0, 'price' => 5000, 'stock' => 0, 'category' => 'Services'],
            ['name' => 'IT Consulting', 'type' => 'service', 'cost' => 0, 'price' => 3000, 'stock' => 0, 'category' => 'Services'],
            ['name' => 'Software Installation', 'type' => 'service', 'cost' => 0, 'price' => 800, 'stock' => 0, 'category' => 'Services'],
            ['name' => 'Printer Paper A4', 'type' => 'product', 'cost' => 25, 'price' => 60, 'stock' => 1000, 'category' => 'Office Supplies'],
            ['name' => 'Ink Cartridge', 'type' => 'product', 'cost' => 300, 'price' => 600, 'stock' => 100, 'category' => 'Office Supplies'],
            ['name' => 'Desk Chair', 'type' => 'product', 'cost' => 800, 'price' => 1500, 'stock' => 40, 'category' => 'Furniture'],
            ['name' => 'Office Desk', 'type' => 'product', 'cost' => 2000, 'price' => 3500, 'stock' => 25, 'category' => 'Furniture'],
            ['name' => 'Network Cable', 'type' => 'product', 'cost' => 40, 'price' => 100, 'stock' => 300, 'category' => 'Accessories'],
            ['name' => 'Router', 'type' => 'product', 'cost' => 400, 'price' => 800, 'stock' => 60, 'category' => 'Electronics'],
            ['name' => 'External Hard Drive 1TB', 'type' => 'product', 'cost' => 600, 'price' => 1200, 'stock' => 80, 'category' => 'Electronics'],
            ['name' => 'USB Flash Drive 32GB', 'type' => 'product', 'cost' => 80, 'price' => 200, 'stock' => 250, 'category' => 'Accessories'],
            ['name' => 'Laptop Bag', 'type' => 'product', 'cost' => 150, 'price' => 400, 'stock' => 90, 'category' => 'Accessories'],
            ['name' => 'Screen Protector', 'type' => 'product', 'cost' => 20, 'price' => 50, 'stock' => 400, 'category' => 'Accessories'],
            ['name' => 'Phone Charger', 'type' => 'product', 'cost' => 25, 'price' => 70, 'stock' => 350, 'category' => 'Accessories'],
            ['name' => 'Power Bank', 'type' => 'product', 'cost' => 200, 'price' => 500, 'stock' => 120, 'category' => 'Electronics'],
        ];

        foreach ($productData as $data) {
            $products[] = GoodsAndService::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'name' => $data['name'],
                'type' => $data['type'],
                'description' => "Quality {$data['name']}",
                'sku' => 'SKU-' . strtoupper(substr(str_replace(' ', '', $data['name']), 0, 8)) . rand(100, 999),
                'barcode' => 'BC' . rand(100000000, 999999999),
                'cost_price' => $data['cost'],
                'selling_price' => $data['price'],
                'current_stock' => $data['stock'],
                'minimum_stock' => $data['type'] === 'product' ? max(10, $data['stock'] * 0.2) : 0,
                'unit' => 'pcs',
                'category' => $data['category'],
                'is_active' => true,
                'track_stock' => $data['type'] === 'product',
            ]);
        }

        return $products;
    }

    private function createInitialStock($organization, $products, $user): void
    {
        foreach ($products as $product) {
            if ($product->track_stock && $product->current_stock > 0) {
                StockMovement::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $organization->id,
                    'goods_service_id' => $product->id,
                    'movement_type' => 'in',
                    'quantity' => $product->current_stock,
                    'reference_number' => 'INIT-' . date('Ymd'),
                    'notes' => 'Initial stock',
                    'created_by_id' => $user->id,
                    'created_at' => Carbon::now()->subMonth(),
                ]);
            }
        }
    }

    private function createSales($organization, $customers, $products, $accounts, $teamMember): array
    {
        $sales = [];
        $startDate = Carbon::now()->subMonth();
        $endDate = Carbon::now();
        $daysDiff = $startDate->diffInDays($endDate);

        // Create 80-120 sales over the month
        $numSales = rand(80, 120);
        $productProducts = collect($products)->filter(fn($p) => $p->type === 'product')->values()->all();

        for ($i = 0; $i < $numSales; $i++) {
            $saleDate = $startDate->copy()->addDays(rand(0, $daysDiff))->setTime(rand(8, 18), rand(0, 59));
            $customer = $customers[array_rand($customers)];
            $account = $accounts[array_rand($accounts)];
            
            // Random payment method (use valid enum values)
            $paymentMethods = ['cash', 'card', 'mobile_money'];
            $paymentMethod = $paymentMethods[array_rand($paymentMethods)];

            // Create sale items (1-5 items per sale)
            $numItems = rand(1, 5);
            $items = [];
            $subtotal = 0;

            for ($j = 0; $j < $numItems; $j++) {
                $product = $productProducts[array_rand($productProducts)];
                // For products, ensure we don't sell more than available stock
                $maxQuantity = $product->type === 'product' && $product->track_stock 
                    ? min(5, max(1, floor($product->current_stock))) 
                    : 1;
                $quantity = rand(1, $maxQuantity);
                $unitPrice = $product->selling_price;
                $itemTotal = $quantity * $unitPrice;
                $subtotal += $itemTotal;

                $items[] = [
                    'goods_service_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'barcode' => $product->barcode,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'cost_price' => $product->cost_price,
                    'total' => $itemTotal,
                    'display_order' => $j + 1,
                ];
            }

            $taxAmount = $subtotal * 0.16; // 16% VAT
            $discountAmount = rand(0, 100) < 20 ? $subtotal * (rand(5, 15) / 100) : 0; // 20% chance of discount
            $totalAmount = $subtotal + $taxAmount - $discountAmount;

            $sale = Sale::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                // sale_number will be auto-generated by the model
                'total_amount' => $totalAmount,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'payment_method' => $paymentMethod,
                'payment_reference' => $paymentMethod !== 'cash' ? 'REF' . rand(100000, 999999) : null,
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'money_account_id' => $account->id,
                'cashier_id' => $teamMember->id, // Use the team member as cashier
                'status' => 'completed',
                'sale_date' => $saleDate,
                'created_at' => $saleDate,
                'updated_at' => $saleDate,
            ]);

            // Create sale items
            foreach ($items as $itemData) {
                SaleItem::create([
                    'id' => (string) Str::uuid(),
                    'sale_id' => $sale->id,
                    'goods_service_id' => $itemData['goods_service_id'],
                    'product_name' => $itemData['product_name'],
                    'sku' => $itemData['sku'],
                    'barcode' => $itemData['barcode'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'cost_price' => $itemData['cost_price'],
                    'total' => $itemData['total'],
                    'display_order' => $itemData['display_order'],
                ]);
            }

            $sales[] = $sale;
        }

        return $sales;
    }

    private function createInvoices($organization, $customers, $products): array
    {
        $invoices = [];
        $startDate = Carbon::now()->subMonth();
        $endDate = Carbon::now();
        $daysDiff = $startDate->diffInDays($endDate);

        // Create 25-35 invoices
        $numInvoices = rand(25, 35);
        $productsCollection = collect($products);

        for ($i = 0; $i < $numInvoices; $i++) {
            $invoiceDate = $startDate->copy()->addDays(rand(0, $daysDiff));
            $dueDate = $invoiceDate->copy()->addDays(rand(15, 45));
            $customer = $customers[array_rand($customers)];

            // Create invoice items (1-8 items)
            $numItems = rand(1, 8);
            $items = [];
            $subtotal = 0;

            for ($j = 0; $j < $numItems; $j++) {
                $product = $productsCollection->random();
                $quantity = rand(1, 10);
                $unitPrice = $product->selling_price;
                $itemTotal = $quantity * $unitPrice;
                $subtotal += $itemTotal;

                $items[] = [
                    'goods_service_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $itemTotal,
                ];
            }

            $taxAmount = $subtotal * 0.16;
            $discountAmount = rand(0, 100) < 15 ? $subtotal * (rand(5, 10) / 100) : 0;
            $totalAmount = $subtotal + $taxAmount - $discountAmount;

            // Random status
            $statuses = ['draft', 'sent', 'paid', 'overdue', 'cancelled'];
            $weights = [5, 30, 40, 20, 5]; // Weighted random
            $status = $statuses[$this->weightedRandom($weights)];
            $paidAmount = $status === 'paid' ? $totalAmount : ($status === 'sent' ? rand(0, $totalAmount * 0.5) : 0);

            $invoice = Invoice::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'customer_id' => $customer->id,
                // invoice_number will be auto-generated by the model
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'status' => $status,
                'notes' => rand(0, 100) < 30 ? 'Payment terms: Net 30' : null,
                'created_at' => $invoiceDate,
                'updated_at' => $invoiceDate,
            ]);

            // Create invoice items
            foreach ($items as $index => $itemData) {
                $product = $productsCollection->firstWhere('id', $itemData['goods_service_id']);
                InvoiceItem::create([
                    'id' => (string) Str::uuid(),
                    'invoice_id' => $invoice->id,
                    'goods_service_id' => $itemData['goods_service_id'],
                    'description' => $product ? $product->name : 'Item',
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total' => $itemData['total'],
                    'display_order' => $index + 1,
                ]);
            }

            $invoices[] = $invoice;
        }

        return $invoices;
    }

    private function createPayments($organization, $invoices, $accounts, $user): void
    {
        $paidInvoices = collect($invoices)->filter(fn($inv) => $inv->status === 'paid' || $inv->paid_amount > 0);

        foreach ($paidInvoices as $invoice) {
            if ($invoice->paid_amount > 0) {
                $paymentDate = $invoice->invoice_date->copy()->addDays(rand(1, 30));
                $account = $accounts[array_rand($accounts)];

                $payment = Payment::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $organization->id,
                    'customer_id' => $invoice->customer_id,
                    'payment_number' => 'PAY-' . $paymentDate->format('Ymd') . '-' . rand(1000, 9999),
                    'amount' => $invoice->paid_amount,
                    'currency' => 'ZMW',
                    'payment_date' => $paymentDate,
                    'payment_method' => ['cash', 'mobile_money', 'card', 'bank_transfer', 'cheque', 'other'][array_rand(['cash', 'mobile_money', 'card', 'bank_transfer', 'cheque', 'other'])],
                    'payment_reference' => 'REF' . rand(100000, 999999),
                    'money_account_id' => $account->id,
                    'notes' => "Payment for invoice {$invoice->invoice_number}",
                    'created_at' => $paymentDate,
                    'updated_at' => $paymentDate,
                ]);

                PaymentAllocation::create([
                    'id' => (string) Str::uuid(),
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $invoice->paid_amount,
                ]);
            }
        }
    }

    private function createQuotes($organization, $customers, $products): array
    {
        $quotes = [];
        $startDate = Carbon::now()->subMonth();
        $endDate = Carbon::now();
        $daysDiff = $startDate->diffInDays($endDate);

        // Create 10-15 quotes
        $numQuotes = rand(10, 15);
        $productsCollection = collect($products);

        for ($i = 0; $i < $numQuotes; $i++) {
            $quoteDate = $startDate->copy()->addDays(rand(0, $daysDiff));
            $expiryDate = $quoteDate->copy()->addDays(30);
            $customer = $customers[array_rand($customers)];

            // Create quote items (1-6 items)
            $numItems = rand(1, 6);
            $items = [];
            $subtotal = 0;

            for ($j = 0; $j < $numItems; $j++) {
                $product = $productsCollection->random();
                $quantity = rand(1, 8);
                $unitPrice = $product->selling_price;
                $itemTotal = $quantity * $unitPrice;
                $subtotal += $itemTotal;

                $items[] = [
                    'goods_service_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $itemTotal,
                ];
            }

            $taxAmount = $subtotal * 0.16;
            $discountAmount = rand(0, 100) < 20 ? $subtotal * (rand(5, 15) / 100) : 0;
            $totalAmount = $subtotal + $taxAmount - $discountAmount;

            $statuses = ['draft', 'sent', 'accepted', 'rejected', 'expired'];
            $status = $statuses[array_rand($statuses)];

            $quote = Quote::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'customer_id' => $customer->id,
                // quote_number will be auto-generated by the model
                'quote_date' => $quoteDate,
                'expiry_date' => $expiryDate,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'status' => $status,
                'notes' => rand(0, 100) < 40 ? 'Valid for 30 days' : null,
                'created_at' => $quoteDate,
                'updated_at' => $quoteDate,
            ]);

            // Create quote items
            foreach ($items as $index => $itemData) {
                $product = $productsCollection->firstWhere('id', $itemData['goods_service_id']);
                QuoteItem::create([
                    'id' => (string) Str::uuid(),
                    'quote_id' => $quote->id,
                    'goods_service_id' => $itemData['goods_service_id'],
                    'description' => $product ? $product->name : 'Item',
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total' => $itemData['total'],
                    'display_order' => $index + 1,
                ]);
            }

            $quotes[] = $quote;
        }

        return $quotes;
    }

    private function createExpenses($organization, $accounts, $user): void
    {
        $startDate = Carbon::now()->subMonth();
        $endDate = Carbon::now();
        $daysDiff = $startDate->diffInDays($endDate);

        $expenseCategories = [
            'Rent', 'Utilities', 'Salaries', 'Marketing', 'Office Supplies',
            'Travel', 'Professional Services', 'Insurance', 'Maintenance', 'Software'
        ];

        // Create 30-50 expense movements
        $numExpenses = rand(30, 50);

        for ($i = 0; $i < $numExpenses; $i++) {
            $expenseDate = $startDate->copy()->addDays(rand(0, $daysDiff))->setTime(rand(8, 18), rand(0, 59));
            $account = $accounts[array_rand($accounts)];
            $category = $expenseCategories[array_rand($expenseCategories)];
            $amount = match($category) {
                'Rent' => rand(10000, 20000),
                'Salaries' => rand(15000, 50000),
                'Utilities' => rand(500, 2000),
                'Marketing' => rand(1000, 5000),
                'Office Supplies' => rand(200, 1000),
                'Travel' => rand(500, 3000),
                'Professional Services' => rand(2000, 8000),
                'Insurance' => rand(1000, 3000),
                'Maintenance' => rand(500, 2000),
                'Software' => rand(500, 2500),
                default => rand(200, 2000),
            };

            MoneyMovement::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'flow_type' => 'expense',
                'amount' => $amount,
                'currency' => 'ZMW',
                'transaction_date' => $expenseDate,
                'from_account_id' => $account->id,
                'description' => $category . ' - ' . ['Monthly payment', 'Service fee', 'Purchase', 'Subscription', 'Payment'][array_rand(['Monthly payment', 'Service fee', 'Purchase', 'Subscription', 'Payment'])],
                'category' => $category,
                'status' => 'approved',
                'created_by_id' => $user->id,
                'created_at' => $expenseDate,
                'updated_at' => $expenseDate,
            ]);
        }
    }

    private function createBudgets($organization): void
    {
        $budgetCategories = [
            'Marketing' => 10000,
            'Office Supplies' => 5000,
            'Travel' => 8000,
            'Professional Services' => 15000,
            'Utilities' => 6000,
            'Maintenance' => 4000,
            'Rent' => 15000,
            'Salaries' => 50000,
            'Insurance' => 3000,
            'Software' => 2500,
        ];

        foreach ($budgetCategories as $category => $amount) {
            BudgetLine::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'name' => $category . ' Budget',
                'category' => $category,
                'amount' => $amount,
                'period' => 'monthly',
                'start_date' => Carbon::now()->startOfMonth(),
                'end_date' => Carbon::now()->endOfMonth(),
                'notes' => 'Monthly budget allocation',
            ]);
        }
    }

    private function createDepartments($organization, $teamMember): array
    {
        $departments = [];
        $departmentData = [
            ['name' => 'Sales', 'description' => 'Sales and customer relations'],
            ['name' => 'Operations', 'description' => 'Day-to-day operations'],
            ['name' => 'Finance', 'description' => 'Financial management'],
            ['name' => 'Human Resources', 'description' => 'HR and employee management'],
            ['name' => 'IT', 'description' => 'Information technology'],
        ];

        foreach ($departmentData as $data) {
            $departments[] = Department::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'name' => $data['name'],
                'description' => $data['description'],
                'manager_id' => $teamMember->id,
                'is_active' => true,
            ]);
        }

        return $departments;
    }

    private function createTeamMembers($organization, $departments): array
    {
        $teamMembers = [];
        $names = [
            ['first' => 'Alice', 'last' => 'Mwansa', 'email' => 'alice.mwansa@example.com'],
            ['first' => 'Bob', 'last' => 'Banda', 'email' => 'bob.banda@example.com'],
            ['first' => 'Carol', 'last' => 'Phiri', 'email' => 'carol.phiri@example.com'],
            ['first' => 'David', 'last' => 'Tembo', 'email' => 'david.tembo@example.com'],
            ['first' => 'Eve', 'last' => 'Mulenga', 'email' => 'eve.mulenga@example.com'],
            ['first' => 'Frank', 'last' => 'Mwanza', 'email' => 'frank.mwanza@example.com'],
            ['first' => 'Grace', 'last' => 'Chanda', 'email' => 'grace.chanda@example.com'],
            ['first' => 'Henry', 'last' => 'Ngoma', 'email' => 'henry.ngoma@example.com'],
        ];

        $jobTitles = ['Sales Representative', 'Accountant', 'Operations Manager', 'HR Assistant', 'IT Support', 'Customer Service', 'Marketing Specialist', 'Administrator'];
        $salaries = [5000, 8000, 12000, 6000, 7000, 5500, 9000, 6500];

        foreach ($names as $index => $name) {
            $department = $departments[array_rand($departments)];
            $hireDate = Carbon::now()->subMonths(rand(3, 24));

            $teamMembers[] = TeamMember::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'first_name' => $name['first'],
                'last_name' => $name['last'],
                'email' => $name['email'],
                'phone' => '097' . rand(1000000, 9999999),
                'employee_number' => 'EMP' . str_pad($index + 2, 3, '0', STR_PAD_LEFT),
                'job_title' => $jobTitles[$index] ?? 'Employee',
                'department_id' => $department->id,
                'hire_date' => $hireDate,
                'salary' => $salaries[$index] ?? 6000,
                'is_active' => true,
            ]);
        }

        return $teamMembers;
    }

    private function createLeaveTypes($organization): array
    {
        $leaveTypes = [];
        $types = [
            ['name' => 'Annual Leave', 'max_days' => 21, 'can_carry_forward' => true, 'max_carry_forward' => 5],
            ['name' => 'Sick Leave', 'max_days' => 10, 'can_carry_forward' => false, 'max_carry_forward' => 0],
            ['name' => 'Maternity Leave', 'max_days' => 90, 'can_carry_forward' => false, 'max_carry_forward' => 0],
            ['name' => 'Paternity Leave', 'max_days' => 5, 'can_carry_forward' => false, 'max_carry_forward' => 0],
            ['name' => 'Bereavement Leave', 'max_days' => 3, 'can_carry_forward' => false, 'max_carry_forward' => 0],
        ];

        foreach ($types as $type) {
            $leaveTypes[] = LeaveType::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'name' => $type['name'],
                'description' => $type['name'] . ' policy',
                'maximum_days_per_year' => $type['max_days'],
                'can_carry_forward' => $type['can_carry_forward'],
                'max_carry_forward_days' => $type['max_carry_forward'],
                'is_active' => true,
            ]);
        }

        return $leaveTypes;
    }

    private function createLeaveRequests($organization, $teamMembers, $leaveTypes, $user): array
    {
        $leaveRequests = [];
        $numRequests = rand(15, 25);

        for ($i = 0; $i < $numRequests; $i++) {
            $teamMember = $teamMembers[array_rand($teamMembers)];
            $leaveType = $leaveTypes[array_rand($leaveTypes)];
            
            $startDate = Carbon::now()->subDays(rand(0, 60));
            $days = rand(1, min(7, $leaveType->maximum_days_per_year));
            $endDate = $startDate->copy()->addDays($days - 1);

            $statuses = ['pending', 'approved', 'rejected', 'cancelled'];
            $status = $statuses[array_rand($statuses)];

            $leaveRequest = LeaveRequest::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'team_member_id' => $teamMember->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'number_of_days' => $days,
                'reason' => ['Family emergency', 'Vacation', 'Medical appointment', 'Personal matters', 'Holiday'][array_rand(['Family emergency', 'Vacation', 'Medical appointment', 'Personal matters', 'Holiday'])],
                'status' => $status,
                'approved_by_id' => $status === 'approved' ? $user->id : null,
                'approved_at' => $status === 'approved' ? $startDate->copy()->subDays(rand(1, 5)) : null,
                'comments' => $status === 'approved' ? 'Approved' : ($status === 'rejected' ? 'Not approved' : null),
            ]);

            $leaveRequests[] = $leaveRequest;
        }

        return $leaveRequests;
    }

    private function createProjects($organization, $user, $teamMembers): array
    {
        $projects = [];
        $projectData = [
            ['name' => 'Website Redesign', 'description' => 'Complete website redesign project'],
            ['name' => 'Mobile App Development', 'description' => 'Develop mobile application'],
            ['name' => 'Marketing Campaign Q1', 'description' => 'Q1 marketing campaign'],
            ['name' => 'System Migration', 'description' => 'Migrate to new system'],
            ['name' => 'Customer Portal', 'description' => 'Build customer portal'],
            ['name' => 'Inventory Management', 'description' => 'Implement inventory system'],
        ];

        foreach ($projectData as $data) {
            $startDate = Carbon::now()->subMonths(rand(1, 6));
            $endDate = $startDate->copy()->addMonths(rand(2, 6));
            $progress = rand(0, 100);
            $statuses = ['planning', 'in_progress', 'on_hold', 'completed', 'cancelled'];
            $status = $progress >= 100 ? 'completed' : ($progress > 0 ? 'in_progress' : 'planning');
            $priorities = ['low', 'medium', 'high', 'urgent'];
            $priority = $priorities[array_rand($priorities)];

            $projects[] = Project::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'name' => $data['name'],
                'description' => $data['description'],
                'status' => $status,
                'priority' => $priority,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'target_completion_date' => $endDate,
                'progress_percentage' => $progress,
                'project_manager_id' => $user->id,
                'created_by_id' => $user->id,
                'notes' => 'Project notes and updates',
            ]);
        }

        return $projects;
    }

    private function createPayrollRuns($organization, $teamMembers, $user): array
    {
        $payrollRuns = [];
        $numRuns = 3; // Last 3 months

        for ($i = 0; $i < $numRuns; $i++) {
            $periodStart = Carbon::now()->subMonths($i)->startOfMonth();
            $periodEnd = $periodStart->copy()->endOfMonth();
            $processedAt = $periodEnd->copy()->addDays(rand(1, 3));

            $payrollRun = PayrollRun::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'pay_period' => $periodStart->format('F Y'),
                'start_date' => $periodStart,
                'end_date' => $periodEnd,
                'status' => 'processed',
                'total_amount' => 0, // Will be calculated
                'created_by_id' => $user->id,
                'processed_at' => $processedAt,
                'notes' => 'Monthly payroll run',
            ]);

            $totalAmount = 0;

            // Create payroll items for each team member
            foreach ($teamMembers as $member) {
                $basicSalary = $member->salary ?? 6000;
                $allowances = [
                    ['name' => 'Transport', 'amount' => rand(500, 1000)],
                    ['name' => 'Housing', 'amount' => rand(1000, 2000)],
                ];
                $deductions = [
                    ['name' => 'Tax', 'amount' => $basicSalary * 0.15],
                    ['name' => 'Pension', 'amount' => $basicSalary * 0.05],
                ];

                $allowancesTotal = collect($allowances)->sum('amount');
                $deductionsTotal = collect($deductions)->sum('amount');
                $grossPay = $basicSalary + $allowancesTotal;
                $netPay = $grossPay - $deductionsTotal;
                $totalAmount += $netPay;

                PayrollItem::create([
                    'id' => (string) Str::uuid(),
                    'payroll_run_id' => $payrollRun->id,
                    'team_member_id' => $member->id,
                    'basic_salary' => $basicSalary,
                    'allowances' => $allowances,
                    'deductions' => $deductions,
                    'gross_pay' => $grossPay,
                    'total_deductions' => $deductionsTotal,
                    'net_pay' => $netPay,
                    'payment_method' => ['bank_transfer', 'cash', 'mobile_money'][array_rand(['bank_transfer', 'cash', 'mobile_money'])],
                    'payment_date' => $processedAt,
                ]);
            }

            $payrollRun->update(['total_amount' => $totalAmount]);
            $payrollRuns[] = $payrollRun;
        }

        return $payrollRuns;
    }

    private function createOKRs($organization, $user, $teamMembers): array
    {
        $okrs = [];
        $okrData = [
            ['title' => 'Increase Sales Revenue', 'description' => 'Achieve 20% revenue growth'],
            ['title' => 'Improve Customer Satisfaction', 'description' => 'Reach 90% customer satisfaction'],
            ['title' => 'Expand Market Presence', 'description' => 'Enter 3 new markets'],
        ];

        $quarters = ['Q1', 'Q2', 'Q3', 'Q4'];
        $currentQuarter = 'Q' . ceil(Carbon::now()->month / 3);

        foreach ($okrData as $data) {
            $startDate = Carbon::now()->startOfQuarter();
            $endDate = Carbon::now()->endOfQuarter();
            $progress = rand(20, 90);
            $statuses = ['not_started', 'in_progress', 'completed', 'cancelled'];
            $status = $progress >= 100 ? 'completed' : ($progress > 0 ? 'in_progress' : 'not_started');

            $okr = OKR::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'quarter' => $currentQuarter,
                'status' => $status,
                'owner_id' => $user->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'progress_percentage' => $progress,
                'created_by_id' => $user->id,
                'notes' => 'OKR notes and updates',
            ]);

            // Create key results
            $numKeyResults = rand(2, 4);
            for ($i = 0; $i < $numKeyResults; $i++) {
                $targetValue = rand(100, 1000);
                $currentValue = (int) ($targetValue * ($progress / 100));
                $krProgress = $targetValue > 0 ? (int) (($currentValue / $targetValue) * 100) : 0;

                KeyResult::create([
                    'id' => (string) Str::uuid(),
                    'okr_id' => $okr->id,
                    'title' => 'Key Result ' . ($i + 1),
                    'description' => 'Description for key result ' . ($i + 1),
                    'type' => 'number',
                    'target_value' => $targetValue,
                    'current_value' => $currentValue,
                    'unit' => 'units',
                    'progress_percentage' => $krProgress,
                    'status' => $krProgress >= 100 ? 'completed' : ($krProgress > 0 ? 'in_progress' : 'not_started'),
                    'display_order' => $i + 1,
                ]);
            }

            $okrs[] = $okr;
        }

        return $okrs;
    }

    private function createDocuments($organization, $user): array
    {
        $documents = [];
        $documentData = [
            ['name' => 'Company Policy', 'category' => 'Policy', 'type' => 'document'],
            ['name' => 'Employee Handbook', 'category' => 'Handbook', 'type' => 'document'],
            ['name' => 'Financial Report Q1', 'category' => 'Report', 'type' => 'report'],
            ['name' => 'Marketing Plan 2024', 'category' => 'Plan', 'type' => 'document'],
            ['name' => 'Contract Template', 'category' => 'Template', 'type' => 'template'],
        ];

        foreach ($documentData as $data) {
            $documents[] = Document::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'name' => $data['name'],
                'description' => 'Description for ' . $data['name'],
                'category' => $data['category'],
                'type' => $data['type'],
                'status' => 'active',
                'file_name' => strtolower(str_replace(' ', '_', $data['name'])) . '.pdf',
                'file_size' => rand(100000, 5000000),
                'mime_type' => 'application/pdf',
                'created_by_id' => $user->id,
            ]);
        }

        return $documents;
    }

    private function createAddyState($organization): AddyState
    {
        return AddyState::create([
            'organization_id' => $organization->id,
            'focus_area' => 'sales',
            'urgency' => rand(30, 80) / 100,
            'context' => 'Business is performing well with steady sales growth. Some inventory items are running low.',
            'mood' => ['neutral', 'concerned', 'optimistic'][array_rand(['neutral', 'concerned', 'optimistic'])],
            'perception_data' => [
                'sales_trend' => 'increasing',
                'inventory_status' => 'moderate',
                'cash_flow' => 'positive',
            ],
            'priorities' => [
                ['item' => 'Restock low inventory items', 'priority' => 0.8],
                ['item' => 'Follow up on overdue invoices', 'priority' => 0.7],
            ],
            'last_thought_cycle' => Carbon::now()->subHours(rand(1, 12)),
        ]);
    }

    private function createAddyInsights($organization, $addyState): void
    {
        $insightTypes = ['alert', 'suggestion', 'observation', 'achievement'];
        $categories = ['money', 'sales', 'people', 'inventory', 'cross-section'];

        for ($i = 0; $i < 10; $i++) {
            $type = $insightTypes[array_rand($insightTypes)];
            $category = $categories[array_rand($categories)];
            $statuses = ['active', 'dismissed', 'completed'];
            $status = $statuses[array_rand($statuses)];

            $titles = [
                'Low Stock Alert',
                'Revenue Growth Opportunity',
                'Employee Performance Review Due',
                'Cash Flow Positive',
                'Customer Satisfaction High',
                'Expense Reduction Suggestion',
                'New Customer Acquisition',
                'Payment Reminder Needed',
                'Inventory Optimization',
                'Sales Target Achievement',
            ];

            AddyInsight::create([
                'organization_id' => $organization->id,
                'addy_state_id' => $addyState->id,
                'type' => $type,
                'category' => $category,
                'title' => $titles[$i] ?? 'Insight ' . ($i + 1),
                'description' => 'Detailed description for ' . ($titles[$i] ?? 'insight ' . ($i + 1)),
                'priority' => rand(50, 100) / 100,
                'is_actionable' => rand(0, 1) === 1,
                'suggested_actions' => [
                    ['action' => 'Review inventory levels', 'url' => '/inventory'],
                    ['action' => 'Check sales reports', 'url' => '/sales'],
                ],
                'action_url' => '/dashboard',
                'status' => $status,
                'dismissed_at' => $status === 'dismissed' ? Carbon::now()->subDays(rand(1, 5)) : null,
                'completed_at' => $status === 'completed' ? Carbon::now()->subDays(rand(1, 3)) : null,
                'expires_at' => rand(0, 1) === 1 ? Carbon::now()->addDays(rand(7, 30)) : null,
            ]);
        }
    }

    private function createAddyChatMessages($organization, $user): void
    {
        $messages = [
            ['role' => 'user', 'content' => 'What are my sales for this month?'],
            ['role' => 'assistant', 'content' => 'Your sales for this month are K' . rand(50000, 200000) . '. This is a ' . rand(5, 25) . '% increase from last month.'],
            ['role' => 'user', 'content' => 'Show me low stock items'],
            ['role' => 'assistant', 'content' => 'You have ' . rand(3, 8) . ' items with low stock. Would you like me to create purchase orders for these items?'],
            ['role' => 'user', 'content' => 'What invoices are overdue?'],
            ['role' => 'assistant', 'content' => 'You have ' . rand(2, 6) . ' overdue invoices totaling K' . rand(10000, 50000) . '. Should I send reminder emails?'],
        ];

        foreach ($messages as $message) {
            AddyChatMessage::create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'role' => $message['role'],
                'content' => $message['content'],
                'metadata' => ['intent' => 'query', 'context' => 'dashboard'],
            ]);
        }
    }

    private function createAddyActions($organization, $user): void
    {
        $actionTypes = [
            'create_transaction',
            'send_invoice_reminders',
            'create_purchase_order',
            'update_inventory',
            'generate_report',
        ];

        $categories = ['money', 'sales', 'people', 'reports'];
        $statuses = ['pending', 'confirmed', 'executed', 'failed', 'cancelled'];

        for ($i = 0; $i < 8; $i++) {
            $actionType = $actionTypes[array_rand($actionTypes)];
            $status = $statuses[array_rand($statuses)];

            AddyAction::create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'action_type' => $actionType,
                'category' => $categories[array_rand($categories)],
                'status' => $status,
                'parameters' => ['param1' => 'value1', 'param2' => 'value2'],
                'preview_data' => ['preview' => 'Action preview data'],
                'result' => $status === 'executed' ? ['success' => true, 'message' => 'Action completed successfully'] : null,
                'confirmed_at' => in_array($status, ['confirmed', 'executed']) ? Carbon::now()->subHours(rand(1, 24)) : null,
                'executed_at' => $status === 'executed' ? Carbon::now()->subHours(rand(1, 12)) : null,
                'was_successful' => $status === 'executed',
            ]);
        }
    }

    private function createSupportTickets($organization, $user): array
    {
        $supportTickets = [];
        $numTickets = rand(8, 15);

        $subjects = [
            'Login issues',
            'Feature request',
            'Bug report',
            'Account question',
            'Billing inquiry',
            'Technical support',
            'General question',
            'Performance issue',
        ];

        $statuses = ['open', 'in_progress', 'resolved', 'closed'];
        $priorities = ['low', 'medium', 'high', 'urgent'];
        $categories = ['technical', 'billing', 'feature', 'general'];

        for ($i = 0; $i < $numTickets; $i++) {
            $status = $statuses[array_rand($statuses)];
            $priority = $priorities[array_rand($priorities)];
            $category = $categories[array_rand($categories)];
            $createdAt = Carbon::now()->subDays(rand(0, 30));

            $ticket = SupportTicket::create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'subject' => $subjects[array_rand($subjects)],
                'description' => 'Detailed description of the support request or issue.',
                'status' => $status,
                'priority' => $priority,
                'category' => $category,
                'first_response_at' => in_array($status, ['in_progress', 'resolved', 'closed']) ? $createdAt->copy()->addHours(rand(1, 24)) : null,
                'resolved_at' => in_array($status, ['resolved', 'closed']) ? $createdAt->copy()->addDays(rand(1, 7)) : null,
                'closed_at' => $status === 'closed' ? $createdAt->copy()->addDays(rand(3, 10)) : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            // Create some messages for the ticket
            $numMessages = rand(1, 4);
            for ($j = 0; $j < $numMessages; $j++) {
                SupportTicketMessage::create([
                    'support_ticket_id' => $ticket->id,
                    'user_id' => $user->id,
                    'message' => 'Message ' . ($j + 1) . ' in the conversation thread.',
                    'is_internal_note' => false,
                    'created_at' => $createdAt->copy()->addHours($j * 2),
                ]);
            }

            $supportTickets[] = $ticket;
        }

        return $supportTickets;
    }

    private function weightedRandom(array $weights): int
    {
        $total = array_sum($weights);
        $random = rand(1, $total);
        $current = 0;

        foreach ($weights as $index => $weight) {
            $current += $weight;
            if ($random <= $current) {
                return $index;
            }
        }

        return 0;
    }
}

