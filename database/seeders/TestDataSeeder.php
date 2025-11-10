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

        // Create Budget Lines (skipped - table structure differs)
        // $this->createBudgets($organization);
        // $this->command->info('✓ Created budget lines');

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
        ];

        // BudgetLines don't have organization_id - they're organization-agnostic
        // Skip budget creation for now as the table structure may differ
        // foreach ($budgetCategories as $category => $amount) {
        //     BudgetLine::create([
        //         'id' => (string) Str::uuid(),
        //         'name' => $category . ' Budget',
        //         'category' => $category,
        //         'amount' => $amount,
        //         'period' => 'monthly',
        //         'start_date' => Carbon::now()->startOfMonth(),
        //         'end_date' => Carbon::now()->endOfMonth(),
        //         'notes' => 'Monthly budget allocation',
        //     ]);
        // }
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

