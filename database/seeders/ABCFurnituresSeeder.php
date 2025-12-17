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
use App\Models\StockMovement;
use App\Models\TeamMember;
use App\Models\PayrollRun;
use App\Models\PayrollItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class ABCFurnituresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or get the demo user
        $user = User::firstOrCreate(
            ['email' => 'demo@abcfurnitures.com'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'ABC Furnitures Admin',
                'password' => Hash::make('demo123456'),
                'email_verified_at' => now(),
            ]
        );

        // Create or get the organization
        $organization = Organization::firstOrCreate(
            ['name' => 'ABC Furnitures'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'ABC Furnitures',
                'slug' => 'abc-furnitures',
            ]
        );

        // Link user to organization if not already linked
        if (!$user->organization_id) {
            $user->update(['organization_id' => $organization->id]);
        }

        // Set authenticated user for model events
        auth()->login($user);

        $this->command->info('Seeding data for ABC Furnitures');
        $this->command->info('Organization: ' . $organization->name);
        $this->command->info('Generating 2 years of historical data...');

        // Create TeamMember for the admin user
        $adminTeamMember = TeamMember::firstOrCreate(
            ['user_id' => $user->id],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'first_name' => 'ABC',
                'last_name' => 'Admin',
                'email' => $user->email,
                'employee_number' => 'EMP001',
                'job_title' => 'Owner/Manager',
                'hire_date' => Carbon::now()->subYears(2),
                'salary' => 15000,
                'is_active' => true,
            ]
        );

        // Create Money Accounts (3 accounts: Bank, Cash, Mobile Money)
        $accounts = $this->createMoneyAccounts($organization, $user);
        $this->command->info('✓ Created ' . count($accounts) . ' money accounts');

        // Create 5 Employees
        $employees = $this->createEmployees($organization);
        $this->command->info('✓ Created ' . count($employees) . ' employees');

        // Create 20 Customers
        $customers = $this->createCustomers($organization);
        $this->command->info('✓ Created ' . count($customers) . ' customers');

        // Create Products (Furniture items)
        $products = $this->createProducts($organization);
        $this->command->info('✓ Created ' . count($products) . ' products');

        // Create initial stock (2 years ago)
        $this->createInitialStock($organization, $products, $user);
        $this->command->info('✓ Created initial stock movements');

        // Create Sales over 2 years
        $sales = $this->createSales($organization, $customers, $products, $accounts, $adminTeamMember, $user);
        $this->command->info('✓ Created ' . count($sales) . ' sales');

        // Create Invoices over 2 years
        $invoices = $this->createInvoices($organization, $customers, $products);
        $this->command->info('✓ Created ' . count($invoices) . ' invoices');

        // Create Payments
        $this->createPayments($organization, $invoices, $accounts, $user);
        $this->command->info('✓ Created payments');

        // Create Quotes over 2 years
        $quotes = $this->createQuotes($organization, $customers, $products);
        $this->command->info('✓ Created ' . count($quotes) . ' quotes');

        // Create Money Movements (Income & Expenses) over 2 years
        $this->createMoneyMovements($organization, $accounts, $user);
        $this->command->info('✓ Created money movements');

        // Create Payroll Runs (monthly for 2 years)
        $this->createPayrollRuns($organization, $employees, $user);
        $this->command->info('✓ Created payroll runs');

        $this->command->info('');
        $this->command->info('✅ Demo account created successfully!');
        $this->command->info('📧 Email: demo@abcfurnitures.com');
        $this->command->info('🔑 Password: demo123456');
    }

    protected function createMoneyAccounts($organization, $user)
    {
        $accounts = [];

        // Main Bank Account
        $accounts[] = MoneyAccount::firstOrCreate(
            ['organization_id' => $organization->id, 'name' => 'Main Bank Account'],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'name' => 'Main Bank Account',
                'type' => 'bank',
                'account_number' => '1234567890',
                'bank_name' => 'Zambia National Bank',
                'opening_balance' => 50000,
                'current_balance' => 50000,
                'currency' => 'ZMW',
                'is_active' => true,
            ]
        );

        // Cash Account
        $accounts[] = MoneyAccount::firstOrCreate(
            ['organization_id' => $organization->id, 'name' => 'Cash Register'],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'name' => 'Cash Register',
                'type' => 'cash',
                'opening_balance' => 10000,
                'current_balance' => 10000,
                'currency' => 'ZMW',
                'is_active' => true,
            ]
        );

        // Mobile Money Account
        $accounts[] = MoneyAccount::firstOrCreate(
            ['organization_id' => $organization->id, 'name' => 'Mobile Money'],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'name' => 'Mobile Money',
                'type' => 'mobile_money',
                'account_number' => '0977123456',
                'bank_name' => 'MTN Mobile Money',
                'opening_balance' => 5000,
                'current_balance' => 5000,
                'currency' => 'ZMW',
                'is_active' => true,
            ]
        );

        return $accounts;
    }

    protected function createEmployees($organization)
    {
        $employees = [];
        $names = [
            ['John', 'Mwansa', 'Sales Manager', 8000],
            ['Mary', 'Banda', 'Sales Associate', 4500],
            ['Peter', 'Phiri', 'Warehouse Manager', 6000],
            ['Grace', 'Tembo', 'Sales Associate', 4500],
            ['David', 'Mwanza', 'Delivery Driver', 3500],
        ];

        foreach ($names as $index => $emp) {
            $employee = TeamMember::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'first_name' => $emp[0],
                'last_name' => $emp[1],
                'email' => strtolower($emp[0] . '.' . $emp[1] . '@abcfurnitures.com'),
                'employee_number' => 'EMP' . str_pad($index + 2, 3, '0', STR_PAD_LEFT),
                'job_title' => $emp[2],
                'hire_date' => Carbon::now()->subYears(2)->addMonths(rand(0, 6)),
                'salary' => $emp[3],
                'is_active' => true,
            ]);
            $employees[] = $employee;
        }

        return $employees;
    }

    protected function createCustomers($organization)
    {
        $customers = [];
        $names = [
            'Lusaka Home Decor', 'Kitwe Office Solutions', 'Ndola Furniture Hub',
            'Livingstone Interiors', 'Kabwe Home Store', 'Chipata Furnishings',
            'Solwezi Office World', 'Mongu Home Center', 'Kasama Furniture Plus',
            'Mazabuka Living Spaces', 'Choma Home Design', 'Mpika Office Supply',
            'Chingola Furniture Mart', 'Mufulira Home Store', 'Luanshya Interiors',
            'Kafue Office Solutions', 'Chililabombwe Home', 'Kalulushi Furnishings',
            'Mansa Furniture World', 'Kapiri Mposhi Home',
        ];

        foreach ($names as $name) {
            $customers[] = Customer::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'name' => $name,
                'email' => strtolower(str_replace(' ', '', $name)) . '@email.com',
                'phone' => '097' . rand(1000000, 9999999),
                'address' => 'Zambia',
                'created_at' => Carbon::now()->subYears(2)->addDays(rand(0, 730)),
            ]);
        }

        return $customers;
    }

    protected function createProducts($organization)
    {
        $products = [];
        $furnitureItems = [
            ['Office Desk', 'desk', 2500, 1500],
            ['Office Chair', 'chair', 800, 500],
            ['Conference Table', 'table', 5000, 3000],
            ['Filing Cabinet', 'cabinet', 1200, 800],
            ['Bookshelf', 'shelf', 1500, 900],
            ['Dining Table', 'table', 3500, 2200],
            ['Dining Chairs (Set of 4)', 'chairs', 2000, 1200],
            ['Sofa Set (3+2)', 'sofa', 8000, 5000],
            ['Coffee Table', 'table', 1200, 700],
            ['Bed Frame (Double)', 'bed', 3000, 1800],
            ['Wardrobe', 'wardrobe', 4500, 2800],
            ['TV Stand', 'stand', 1500, 900],
            ['Reception Desk', 'desk', 4000, 2500],
            ['Guest Chairs (Set of 2)', 'chairs', 1000, 600],
            ['Storage Cabinet', 'cabinet', 2000, 1200],
        ];

        foreach ($furnitureItems as $item) {
            $products[] = GoodsAndService::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'name' => $item[0],
                'type' => 'product',
                'sku' => 'FURN-' . strtoupper(substr($item[1], 0, 3)) . '-' . rand(100, 999),
                'selling_price' => $item[2],
                'cost_price' => $item[3],
                'unit' => 'piece',
                'is_active' => true,
                'created_at' => Carbon::now()->subYears(2),
            ]);
        }

        return $products;
    }

    protected function createInitialStock($organization, $products, $user)
    {
        foreach ($products as $product) {
            StockMovement::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'goods_service_id' => $product->id,
                'movement_type' => 'in',
                'quantity' => rand(10, 50),
                'reference_number' => 'INITIAL-STOCK',
                'notes' => 'Initial stock',
                'created_by_id' => $user->id,
                'created_at' => Carbon::now()->subYears(2),
            ]);
        }
    }

    protected function createSales($organization, $customers, $products, $accounts, $teamMember, $user)
    {
        $sales = [];
        $startDate = Carbon::now()->subYears(2);
        $endDate = Carbon::now();

        // Create sales over 2 years (approximately 2-3 per week)
        $totalSales = 250; // ~2.4 sales per week over 2 years

        for ($i = 0; $i < $totalSales; $i++) {
            $saleDate = $startDate->copy()->addDays(rand(0, $startDate->diffInDays($endDate)));
            $customer = $customers[array_rand($customers)];
            $account = $accounts[array_rand($accounts)];
            $paymentMethods = ['cash', 'mobile_money', 'card', 'credit'];
            $paymentMethod = $paymentMethods[array_rand($paymentMethods)];

            // Determine number of items (1-5 items per sale)
            $numItems = rand(1, 5);
            $selectedProducts = array_rand($products, min($numItems, count($products)));
            if (!is_array($selectedProducts)) {
                $selectedProducts = [$selectedProducts];
            }

            $totalAmount = 0;
            $taxAmount = 0;
            $discountAmount = 0;

            $sale = Sale::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'sale_number' => 'SALE-' . strtoupper(Str::random(8)),
                'total_amount' => 0, // Will update after items
                'tax_amount' => 0,
                'discount_amount' => 0,
                'payment_method' => $paymentMethod,
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'money_account_id' => $account->id,
                'cashier_id' => $teamMember->id,
                'status' => 'completed',
                'sale_date' => $saleDate,
                'created_at' => $saleDate,
            ]);

            foreach ($selectedProducts as $productIndex) {
                $product = $products[$productIndex];
                $quantity = rand(1, 3);
                $unitPrice = $product->selling_price;
                $subtotal = $quantity * $unitPrice;
                
                // Apply discount occasionally (20% of sales)
                $discount = (rand(1, 100) <= 20) ? $subtotal * 0.1 : 0;
                $discountedSubtotal = $subtotal - $discount;
                
                $tax = $discountedSubtotal * 0.16; // 16% VAT
                $lineTotal = $discountedSubtotal + $tax;

                SaleItem::create([
                    'id' => (string) Str::uuid(),
                    'sale_id' => $sale->id,
                    'goods_service_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'cost_price' => $product->cost_price,
                    'total' => $lineTotal,
                    'display_order' => 1,
                ]);

                $totalAmount += $lineTotal;
                $taxAmount += $tax;
                $discountAmount += $discount;

                // Update stock
                StockMovement::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $organization->id,
                    'goods_service_id' => $product->id,
                    'movement_type' => 'out',
                    'quantity' => $quantity,
                    'reference_number' => $sale->sale_number,
                    'notes' => 'Sale: ' . $sale->sale_number,
                    'created_by_id' => $user->id,
                    'created_at' => $saleDate,
                ]);
            }

            $sale->update([
                'total_amount' => $totalAmount,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
            ]);

            // Update account balance
            $account->increment('current_balance', $totalAmount);

            $sales[] = $sale;
        }

        return $sales;
    }

    protected function createInvoices($organization, $customers, $products)
    {
        $invoices = [];
        $startDate = Carbon::now()->subYears(2);
        $endDate = Carbon::now();

        // Create invoices over 2 years (approximately 1-2 per week)
        $totalInvoices = 100;

        for ($i = 0; $i < $totalInvoices; $i++) {
            $invoiceDate = $startDate->copy()->addDays(rand(0, $startDate->diffInDays($endDate)));
            $dueDate = $invoiceDate->copy()->addDays(rand(7, 30));
            $customer = $customers[array_rand($customers)];

            $numItems = rand(1, 4);
            $selectedProducts = array_rand($products, min($numItems, count($products)));
            if (!is_array($selectedProducts)) {
                $selectedProducts = [$selectedProducts];
            }

            $subtotal = 0;
            $taxAmount = 0;

            $invoice = Invoice::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'invoice_number' => 'INV-' . date('Y', $invoiceDate->timestamp) . '-' . str_pad($i + 1, 5, '0', STR_PAD_LEFT) . '-' . strtoupper(Str::random(4)),
                'customer_id' => $customer->id,
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'subtotal' => 0,
                'tax_amount' => 0,
                'total_amount' => 0,
                'paid_amount' => 0,
                'status' => 'sent',
                'created_at' => $invoiceDate,
            ]);

            foreach ($selectedProducts as $productIndex) {
                $product = $products[$productIndex];
                $quantity = rand(1, 5);
                $unitPrice = $product->selling_price;
                $lineSubtotal = $quantity * $unitPrice;
                $tax = $lineSubtotal * 0.16;

                InvoiceItem::create([
                    'id' => (string) Str::uuid(),
                    'invoice_id' => $invoice->id,
                    'goods_service_id' => $product->id,
                    'description' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $lineSubtotal + $tax,
                    'display_order' => 1,
                ]);

                $subtotal += $lineSubtotal;
                $taxAmount += $tax;
            }

            $totalAmount = $subtotal + $taxAmount;

            // Determine status based on due date
            $status = 'sent';
            if ($dueDate < now()) {
                $status = (rand(1, 100) <= 30) ? 'overdue' : 'paid'; // 30% overdue
            } elseif (rand(1, 100) <= 40) {
                $status = 'paid'; // 40% paid early
            }

            $invoice->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'status' => $status,
            ]);

            $invoices[] = $invoice;
        }

        return $invoices;
    }

    protected function createPayments($organization, $invoices, $accounts, $user)
    {
        $paidInvoices = collect($invoices)->where('status', 'paid')->random(40);

        foreach ($paidInvoices as $index => $invoice) {
            $account = $accounts[array_rand($accounts)];
            $paymentDate = $invoice->invoice_date->copy()->addDays(rand(0, 30));

            // Temporarily disable receipt auto-creation by using DB facade
            $paymentId = (string) Str::uuid();
            $paymentNumber = 'PAY-' . date('Ymd', $paymentDate->timestamp) . '-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT) . '-' . strtoupper(Str::random(4));
            
            \DB::table('payments')->insert([
                'id' => $paymentId,
                'organization_id' => $organization->id,
                'payment_number' => $paymentNumber,
                'customer_id' => $invoice->customer_id,
                'amount' => $invoice->total_amount,
                'currency' => 'ZMW',
                'payment_date' => $paymentDate,
                'payment_method' => ['cash', 'mobile_money', 'bank_transfer', 'card'][array_rand(['cash', 'mobile_money', 'bank_transfer', 'card'])],
                'money_account_id' => $account->id,
                'created_at' => $paymentDate,
                'updated_at' => $paymentDate,
            ]);
            
            $payment = Payment::find($paymentId);

            PaymentAllocation::create([
                'id' => (string) Str::uuid(),
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount' => $invoice->total_amount,
            ]);

            $invoice->update([
                'paid_amount' => $invoice->total_amount,
                'status' => 'paid',
            ]);

            $account->increment('current_balance', $invoice->total_amount);
        }
    }

    protected function createQuotes($organization, $customers, $products)
    {
        $quotes = [];
        $startDate = Carbon::now()->subYears(2);
        $endDate = Carbon::now();

        $totalQuotes = 60;

        for ($i = 0; $i < $totalQuotes; $i++) {
            $quoteDate = $startDate->copy()->addDays(rand(0, $startDate->diffInDays($endDate)));
            $expiryDate = $quoteDate->copy()->addDays(30);
            $customer = $customers[array_rand($customers)];

            $numItems = rand(1, 3);
            $selectedProducts = array_rand($products, min($numItems, count($products)));
            if (!is_array($selectedProducts)) {
                $selectedProducts = [$selectedProducts];
            }

            $subtotal = 0;
            $taxAmount = 0;

            $quote = Quote::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'quote_number' => 'QUO-' . date('Y', $quoteDate->timestamp) . '-' . str_pad($i + 1, 5, '0', STR_PAD_LEFT) . '-' . strtoupper(Str::random(4)),
                'customer_id' => $customer->id,
                'quote_date' => $quoteDate,
                'expiry_date' => $expiryDate,
                'subtotal' => 0,
                'tax_amount' => 0,
                'total_amount' => 0,
                'status' => ['sent', 'accepted', 'rejected'][array_rand(['sent', 'accepted', 'rejected'])],
                'created_at' => $quoteDate,
            ]);

            foreach ($selectedProducts as $productIndex) {
                $product = $products[$productIndex];
                $quantity = rand(1, 4);
                $unitPrice = $product->selling_price;
                $lineSubtotal = $quantity * $unitPrice;
                $tax = $lineSubtotal * 0.16;

                QuoteItem::create([
                    'id' => (string) Str::uuid(),
                    'quote_id' => $quote->id,
                    'goods_service_id' => $product->id,
                    'description' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $lineSubtotal + $tax,
                    'display_order' => 1,
                ]);

                $subtotal += $lineSubtotal;
                $taxAmount += $tax;
            }

            $quote->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $subtotal + $taxAmount,
            ]);

            $quotes[] = $quote;
        }

        return $quotes;
    }

    protected function createMoneyMovements($organization, $accounts, $user)
    {
        $startDate = Carbon::now()->subYears(2);
        $endDate = Carbon::now();

        // Income movements (rental income, services, etc.)
        for ($i = 0; $i < 50; $i++) {
            $date = $startDate->copy()->addDays(rand(0, $startDate->diffInDays($endDate)));
            $account = $accounts[array_rand($accounts)];

            $descriptions = [
                'Furniture Assembly Service',
                'Delivery Fee Income',
                'Furniture Rental Income',
                'Consultation Fee',
                'Warranty Service Fee',
            ];

            $amount = rand(200, 2000);
            MoneyMovement::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'to_account_id' => $account->id,
                'amount' => $amount,
                'flow_type' => 'income',
                'category' => 'services',
                'description' => $descriptions[array_rand($descriptions)],
                'transaction_date' => $date,
                'created_by_id' => $user->id,
                'created_at' => $date,
            ]);
        }

        // Expense movements (rent, utilities, supplies, etc.)
        $expenseCategories = [
            'rent' => ['Shop Rent', 'Warehouse Rent'],
            'utilities' => ['Electricity', 'Water', 'Internet'],
            'supplies' => ['Office Supplies', 'Packaging Materials'],
            'marketing' => ['Advertising', 'Social Media Marketing'],
            'maintenance' => ['Equipment Maintenance', 'Vehicle Maintenance'],
            'other' => ['Insurance', 'Professional Fees', 'Bank Charges'],
        ];

        for ($i = 0; $i < 150; $i++) {
            $date = $startDate->copy()->addDays(rand(0, $startDate->diffInDays($endDate)));
            $account = $accounts[array_rand($accounts)];
            $category = array_rand($expenseCategories);
            $descriptions = $expenseCategories[$category];

            $amount = match($category) {
                'rent' => rand(5000, 8000),
                'utilities' => rand(500, 1500),
                'supplies' => rand(200, 800),
                'marketing' => rand(1000, 3000),
                'maintenance' => rand(500, 2000),
                default => rand(300, 1500),
            };

            MoneyMovement::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'from_account_id' => $account->id,
                'amount' => $amount,
                'flow_type' => 'expense',
                'category' => $category,
                'description' => $descriptions[array_rand($descriptions)],
                'transaction_date' => $date,
                'created_by_id' => $user->id,
                'created_at' => $date,
            ]);
        }
    }

    protected function createPayrollRuns($organization, $employees, $user)
    {
        $periodStart = Carbon::now()->subYears(2)->startOfMonth();
        $periodEnd = Carbon::now()->startOfMonth();

        $currentDate = $periodStart->copy();
        $runNumber = 1;

        while ($currentDate->lte($periodEnd)) {
            $payPeriod = $currentDate->format('Y-m');
            $runStartDate = $currentDate->copy()->startOfMonth();
            $runEndDate = $currentDate->copy()->endOfMonth();
            $processDate = $runEndDate->copy()->addDays(rand(1, 5));

            $payrollRun = PayrollRun::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'pay_period' => $payPeriod,
                'start_date' => $runStartDate,
                'end_date' => $runEndDate,
                'status' => 'completed',
                'total_amount' => 0,
                'created_by_id' => $user->id,
                'processed_at' => $processDate,
                'notes' => 'Monthly payroll for ' . $currentDate->format('F Y'),
                'created_at' => $processDate,
            ]);

            $totalAmount = 0;

            foreach ($employees as $employee) {
                $basicSalary = $employee->salary;
                
                // Add allowances (transport, housing, etc.)
                $allowances = [];
                if (rand(1, 100) <= 70) {
                    $allowances[] = ['name' => 'Transport Allowance', 'amount' => rand(300, 800)];
                }
                if (rand(1, 100) <= 40) {
                    $allowances[] = ['name' => 'Housing Allowance', 'amount' => rand(500, 1500)];
                }
                if (rand(1, 100) <= 30) {
                    $allowances[] = ['name' => 'Meal Allowance', 'amount' => rand(200, 500)];
                }

                $allowancesTotal = collect($allowances)->sum('amount');
                $grossPay = $basicSalary + $allowancesTotal;

                // Add deductions (tax, NAPSA, etc.)
                $deductions = [];
                $tax = $grossPay * 0.25; // 25% tax
                $deductions[] = ['name' => 'Income Tax', 'amount' => round($tax, 2)];
                
                $napsa = $grossPay * 0.05; // 5% NAPSA
                $deductions[] = ['name' => 'NAPSA', 'amount' => round($napsa, 2)];

                if (rand(1, 100) <= 20) {
                    $deductions[] = ['name' => 'Loan Deduction', 'amount' => rand(200, 500)];
                }

                $deductionsTotal = collect($deductions)->sum('amount');
                $netPay = $grossPay - $deductionsTotal;

                PayrollItem::create([
                    'id' => (string) Str::uuid(),
                    'payroll_run_id' => $payrollRun->id,
                    'team_member_id' => $employee->id,
                    'basic_salary' => $basicSalary,
                    'allowances' => $allowances,
                    'deductions' => $deductions,
                    'gross_pay' => round($grossPay, 2),
                    'total_deductions' => round($deductionsTotal, 2),
                    'net_pay' => round($netPay, 2),
                    'payment_method' => ['bank_transfer', 'mobile_money', 'cash'][array_rand(['bank_transfer', 'mobile_money', 'cash'])],
                    'payment_date' => $processDate,
                    'created_at' => $processDate,
                ]);

                $totalAmount += $netPay;
            }

            $payrollRun->update(['total_amount' => round($totalAmount, 2)]);

            $currentDate->addMonth();
            $runNumber++;
        }
    }
}

