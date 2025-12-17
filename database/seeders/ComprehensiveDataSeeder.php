<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerPersona;
use App\Models\Vendor;
use App\Models\Prospect;
use App\Models\Quotation;
use App\Models\Bill;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Payment;
use App\Models\MoneyMovement;
use App\Models\GoodsAndService;
use App\Models\MoneyAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class ComprehensiveDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create a test organization
        $organization = Organization::firstOrCreate(
            ['slug' => 'demo-organization'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Demo Organization',
                'slug' => 'demo-organization',
            ]
        );

        // Get or create a test user
        $user = User::firstOrCreate(
            ['email' => 'demo@addy.com'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Demo User',
                'password' => Hash::make('admin1234'),
                'email_verified_at' => now(),
                'organization_id' => $organization->id,
            ]
        );
        
        // Update password if user already exists
        if ($user->wasRecentlyCreated === false) {
            $user->update(['password' => Hash::make('admin1234')]);
        }

        if (!$user->organization_id) {
            $user->update(['organization_id' => $organization->id]);
        }

        // Set authenticated user for model events
        auth()->login($user);

        $this->command->info('Seeding comprehensive data for ' . $organization->name);
        $this->command->info('This will populate all People section data...');

        // 1. Create Customer Personas
        $personas = $this->createCustomerPersonas($organization);
        $this->command->info('✓ Created ' . count($personas) . ' customer personas');

        // 2. Create Customers
        $customers = $this->createCustomers($organization, $personas);
        $this->command->info('✓ Created ' . count($customers) . ' customers');

        // 3. Create Vendors
        $vendors = $this->createVendors($organization);
        $this->command->info('✓ Created ' . count($vendors) . ' vendors');

        // 4. Create Prospects
        $prospects = $this->createProspects($organization);
        $this->command->info('✓ Created ' . count($prospects) . ' prospects');

        // 5. Create Products/Services
        $products = $this->createProducts($organization);
        $this->command->info('✓ Created ' . count($products) . ' products/services');

        // 6. Create Money Accounts
        $accounts = $this->createMoneyAccounts($organization);
        $this->command->info('✓ Created ' . count($accounts) . ' money accounts');

        // 7. Create Invoices
        $invoices = $this->createInvoices($organization, $customers, $products);
        $this->command->info('✓ Created ' . count($invoices) . ' invoices');

        // 8. Create Quotes
        $quotes = $this->createQuotes($organization, $customers, $products);
        $this->command->info('✓ Created ' . count($quotes) . ' quotes');

        // 9. Create Quotations (new People section)
        $quotations = $this->createQuotations($organization, $customers, $prospects);
        $this->command->info('✓ Created ' . count($quotations) . ' quotations');

        // 10. Create Bills
        $bills = $this->createBills($organization, $vendors);
        $this->command->info('✓ Created ' . count($bills) . ' bills');

        // 11. Create Payments
        $payments = $this->createPayments($organization, $customers, $invoices);
        $this->command->info('✓ Created ' . count($payments) . ' payments');

        // 12. Create Money Movements
        $movements = $this->createMoneyMovements($organization, $accounts);
        $this->command->info('✓ Created ' . count($movements) . ' money movements');

        $this->command->info('');
        $this->command->info('✓ Comprehensive data seeding completed!');
        $this->command->info('You can now login with: demo@addy.com / admin1234');
    }

    private function createCustomerPersonas($organization)
    {
        $personas = [
            [
                'name' => 'Small Business',
                'description' => 'Small businesses with 1-10 employees',
                'industry' => 'Retail',
                'size' => 'small',
                'payment_behavior' => 'excellent',
                'color' => '#14b8a6',
                'icon' => '🏪',
            ],
            [
                'name' => 'Enterprise',
                'description' => 'Large enterprises with 100+ employees',
                'industry' => 'Technology',
                'size' => 'enterprise',
                'payment_behavior' => 'good',
                'color' => '#3b82f6',
                'icon' => '🏢',
            ],
            [
                'name' => 'Startup',
                'description' => 'Early-stage startups',
                'industry' => 'Technology',
                'size' => 'small',
                'payment_behavior' => 'fair',
                'color' => '#8b5cf6',
                'icon' => '🚀',
            ],
            [
                'name' => 'Government',
                'description' => 'Government entities and agencies',
                'industry' => 'Public Sector',
                'size' => 'large',
                'payment_behavior' => 'good',
                'color' => '#f59e0b',
                'icon' => '🏛️',
            ],
        ];

        $created = [];
        foreach ($personas as $persona) {
            $slug = Str::slug($persona['name']);
            // Check if persona already exists
            $existing = CustomerPersona::where('organization_id', $organization->id)
                ->where('slug', $slug)
                ->first();
            
            if ($existing) {
                $created[] = $existing;
            } else {
                // Use DB insert to bypass UUID trait since table uses integer IDs
                $id = \Illuminate\Support\Facades\DB::table('customer_personas')->insertGetId([
                    'organization_id' => $organization->id,
                    'name' => $persona['name'],
                    'slug' => $slug,
                    'description' => $persona['description'],
                    'industry' => $persona['industry'],
                    'size' => $persona['size'],
                    'payment_behavior' => $persona['payment_behavior'],
                    'color' => $persona['color'],
                    'icon' => $persona['icon'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $created[] = CustomerPersona::find($id);
            }
        }

        return $created;
    }

    private function createCustomers($organization, $personas)
    {
        $names = [
            'ABC Trading Ltd', 'Global Solutions', 'Tech Innovations', 'Prime Services',
            'Elite Enterprises', 'Premier Corp', 'Apex Industries', 'Summit Group',
            'Nexus Business', 'Vertex Solutions', 'David Mulenga', 'Esther Kunda',
            'John Mwansa', 'Mary Banda', 'Peter Chanda', 'Sarah Phiri',
        ];

        $created = [];
        foreach ($names as $index => $name) {
            $isBusiness = !in_array($name, ['David Mulenga', 'Esther Kunda', 'John Mwansa', 'Mary Banda', 'Peter Chanda', 'Sarah Phiri']);
            $customerCode = 'CUS' . str_pad($index + 1, 6, '0', STR_PAD_LEFT);
            
            // Check if customer already exists
            $existing = Customer::where('organization_id', $organization->id)
                ->where('customer_code', $customerCode)
                ->first();
            
            if ($existing) {
                $created[] = $existing;
            } else {
                $created[] = Customer::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $organization->id,
                    'customer_persona_id' => $personas[array_rand($personas)]->id,
                    'customer_code' => $customerCode,
                    'type' => $isBusiness ? 'business' : 'individual',
                    'name' => $name,
                    'email' => Str::slug($name) . '@example.com',
                    'phone' => '097' . str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                    'company_name' => $isBusiness ? $name : null,
                    'tax_id' => $isBusiness ? 'TAX' . rand(100000, 999999) : null,
                    'billing_address' => rand(1, 100) . ' Main Street, Lusaka',
                    'city' => 'Lusaka',
                    'country' => 'Zambia',
                    'payment_terms' => ['immediate', 'net_15', 'net_30', 'net_60'][array_rand(['immediate', 'net_15', 'net_30', 'net_60'])],
                    'currency' => 'ZMW',
                    'status' => 'active',
                    'first_purchase_date' => Carbon::now()->subMonths(rand(1, 12)),
                    'last_purchase_date' => Carbon::now()->subDays(rand(1, 30)),
                    'total_orders' => rand(1, 50),
                    'lifetime_value' => rand(10000, 500000),
                ]);
            }
        }

        return $created;
    }

    private function createVendors($organization)
    {
        $names = [
            'Office Supplies Co', 'IT Solutions Ltd', 'Marketing Agency', 'Legal Services',
            'Accounting Firm', 'Cleaning Services', 'Security Services', 'Transportation Co',
            'Equipment Rental', 'Maintenance Services', 'Utilities Provider', 'Insurance Co',
        ];

        $created = [];
        foreach ($names as $index => $name) {
            $vendorCode = 'VEN' . str_pad($index + 1, 6, '0', STR_PAD_LEFT);
            
            // Check if vendor already exists
            $existing = Vendor::where('organization_id', $organization->id)
                ->where('vendor_code', $vendorCode)
                ->first();
            
            if ($existing) {
                $created[] = $existing;
            } else {
                $created[] = Vendor::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $organization->id,
                    'vendor_code' => $vendorCode,
                    'type' => 'business',
                    'name' => $name,
                    'email' => Str::slug($name) . '@vendor.com',
                    'phone' => '097' . str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                    'address' => rand(1, 100) . ' Vendor Street, Lusaka',
                    'city' => 'Lusaka',
                    'country' => 'Zambia',
                    'payment_terms' => ['net_15', 'net_30', 'net_60'][array_rand(['net_15', 'net_30', 'net_60'])],
                    'currency' => 'ZMW',
                    'status' => 'active',
                    'first_transaction_date' => Carbon::now()->subMonths(rand(1, 12)),
                    'last_transaction_date' => Carbon::now()->subDays(rand(1, 30)),
                    'total_transactions' => rand(1, 30),
                    'total_spent' => rand(5000, 200000),
                ]);
            }
        }

        return $created;
    }

    private function createProspects($organization)
    {
        $stages = ['lead', 'contacted', 'qualified', 'proposal', 'negotiation'];
        $names = [
            'Future Corp', 'NextGen Solutions', 'Innovation Hub', 'Digital Dynamics',
            'Smart Systems', 'Advanced Tech', 'Modern Solutions', 'Progressive Inc',
        ];

        $created = [];
        foreach ($names as $index => $name) {
            $email = Str::slug($name) . '@prospect.com';
            
            // Check if prospect already exists
            $existing = Prospect::where('organization_id', $organization->id)
                ->where('email', $email)
                ->first();
            
            if ($existing) {
                $created[] = $existing;
            } else {
                $stage = $stages[array_rand($stages)];
                // Use DB insert to bypass model boot method that tries to set prospect_code
                $id = (string) Str::uuid();
                \Illuminate\Support\Facades\DB::table('prospects')->insert([
                    'id' => $id,
                    'organization_id' => $organization->id,
                    'name' => $name,
                    'company_name' => $name,
                    'email' => $email,
                    'phone' => '097' . str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                    'stage' => $stage,
                    'estimated_value' => rand(10000, 200000),
                    'probability' => rand(20, 90),
                    'expected_close_date' => Carbon::now()->addDays(rand(7, 90)),
                    'engagement_score' => rand(30, 100),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $created[] = Prospect::find($id);
            }
        }

        return $created;
    }

    private function createProducts($organization)
    {
        $products = [
            ['name' => 'Office Chair', 'type' => 'product', 'price' => 500],
            ['name' => 'Desk', 'type' => 'product', 'price' => 1200],
            ['name' => 'Laptop', 'type' => 'product', 'price' => 8000],
            ['name' => 'Consulting Service', 'type' => 'service', 'price' => 2000],
            ['name' => 'Web Development', 'type' => 'service', 'price' => 15000],
            ['name' => 'Marketing Campaign', 'type' => 'service', 'price' => 5000],
        ];

        $created = [];
        foreach ($products as $product) {
            $created[] = GoodsAndService::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'name' => $product['name'],
                'type' => $product['type'],
                'selling_price' => $product['price'],
                'cost_price' => $product['type'] === 'product' ? $product['price'] * 0.7 : $product['price'] * 0.5,
                'is_active' => true,
                'current_stock' => $product['type'] === 'product' ? rand(0, 100) : 0,
                'track_stock' => $product['type'] === 'product',
            ]);
        }

        return $created;
    }

    private function createMoneyAccounts($organization)
    {
        $accounts = [
            ['name' => 'Main Bank Account', 'type' => 'bank', 'balance' => 500000],
            ['name' => 'Cash Register', 'type' => 'cash', 'balance' => 50000],
            ['name' => 'Petty Cash', 'type' => 'cash', 'balance' => 5000],
        ];

        $created = [];
        foreach ($accounts as $account) {
            $created[] = MoneyAccount::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'name' => $account['name'],
                'type' => $account['type'],
                'opening_balance' => $account['balance'],
                'current_balance' => $account['balance'],
                'currency' => 'ZMW',
                'is_active' => true,
            ]);
        }

        return $created;
    }

    private function createInvoices($organization, $customers, $products)
    {
        $statuses = ['draft', 'sent', 'overdue', 'paid'];
        $created = [];

        for ($i = 0; $i < 20; $i++) {
            $invoiceNumber = 'INV-' . str_pad($i + 1, 6, '0', STR_PAD_LEFT);
            
            // Check if invoice already exists
            $existing = Invoice::where('organization_id', $organization->id)
                ->where('invoice_number', $invoiceNumber)
                ->first();
            
            if ($existing) {
                continue; // Skip if already exists
            }
            
            $customer = $customers[array_rand($customers)];
            $status = $statuses[array_rand($statuses)];
            $invoiceDate = Carbon::now()->subDays(rand(1, 90));
            $dueDate = $invoiceDate->copy()->addDays(rand(15, 45));

            $invoice = Invoice::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'customer_id' => $customer->id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'status' => $status,
                'subtotal' => 0,
                'tax_amount' => 0,
                'total_amount' => 0,
                'paid_amount' => $status === 'paid' ? 0 : rand(0, 50) / 100, // Random partial payment
            ]);

            // Add items
            $itemCount = rand(1, 5);
            $subtotal = 0;
            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products[array_rand($products)];
                $quantity = rand(1, 10);
                $unitPrice = $product->selling_price ?? 0;
                $total = $quantity * $unitPrice;
                $subtotal += $total;

                InvoiceItem::create([
                    'id' => (string) Str::uuid(),
                    'invoice_id' => $invoice->id,
                    'goods_service_id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $total,
                ]);
            }

            $tax = $subtotal * 0.16; // 16% VAT
            $total = $subtotal + $tax;

            $invoice->update([
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'total_amount' => $total,
                'paid_amount' => $status === 'paid' ? $total : ($invoice->paid_amount * $total),
            ]);

            $created[] = $invoice;
        }

        return $created;
    }

    private function createQuotes($organization, $customers, $products)
    {
        $statuses = ['draft', 'sent', 'accepted', 'rejected'];
        $created = [];

        for ($i = 0; $i < 15; $i++) {
            $customer = $customers[array_rand($customers)];
            $status = $statuses[array_rand($statuses)];
            $quoteDate = Carbon::now()->subDays(rand(1, 60));
            $expiryDate = $quoteDate->copy()->addDays(30);

            $quoteNumber = 'QUO-' . str_pad($i + 1, 6, '0', STR_PAD_LEFT);
            
            // Check if quote already exists
            $existing = Quote::where('organization_id', $organization->id)
                ->where('quote_number', $quoteNumber)
                ->first();
            
            if ($existing) {
                continue; // Skip if already exists
            }
            
            $quote = Quote::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'customer_id' => $customer->id,
                'quote_number' => $quoteNumber,
                'quote_date' => $quoteDate,
                'expiry_date' => $expiryDate,
                'status' => $status,
                'subtotal' => 0,
                'tax_amount' => 0,
                'total_amount' => 0,
            ]);

            // Add items
            $itemCount = rand(1, 4);
            $subtotal = 0;
            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products[array_rand($products)];
                $quantity = rand(1, 8);
                $unitPrice = $product->selling_price ?? 0;
                $total = $quantity * $unitPrice;
                $subtotal += $total;

                QuoteItem::create([
                    'id' => (string) Str::uuid(),
                    'quote_id' => $quote->id,
                    'goods_service_id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $total,
                ]);
            }

            $tax = $subtotal * 0.16;
            $total = $subtotal + $tax;

            $quote->update([
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'total_amount' => $total,
            ]);

            $created[] = $quote;
        }

        return $created;
    }

    private function createQuotations($organization, $customers, $prospects)
    {
        $statuses = ['draft', 'sent', 'viewed'];
        $created = [];

        for ($i = 0; $i < 10; $i++) {
            $quotationNumber = 'QTN-' . str_pad($i + 1, 6, '0', STR_PAD_LEFT);
            
            // Check if quotation already exists
            $existing = \Illuminate\Support\Facades\DB::table('quotations')
                ->where('organization_id', $organization->id)
                ->where('quotation_number', $quotationNumber)
                ->first();
            
            if ($existing) {
                continue; // Skip if already exists
            }
            
            $useCustomer = rand(0, 1);
            $status = $statuses[array_rand($statuses)];
            $issueDate = Carbon::now()->subDays(rand(1, 30));
            $validUntil = $issueDate->copy()->addDays(rand(15, 60));
            
            // Note: quotations table uses integer foreign keys, but customers/prospects use UUIDs
            // Setting to null for now due to schema mismatch
            $user = $organization->users()->first();
            
            // Use DB insert since table uses integer IDs
            $id = \Illuminate\Support\Facades\DB::table('quotations')->insertGetId([
                'organization_id' => $organization->id,
                'customer_id' => null, // Schema mismatch: table expects int, but customers use UUIDs
                'prospect_id' => null, // Schema mismatch: table expects int, but prospects use UUIDs
                'created_by' => $user ? $user->id : 1,
                'quotation_number' => $quotationNumber,
                'title' => 'Quotation for Services - ' . ($i + 1),
                'status' => $status,
                'issue_date' => $issueDate,
                'valid_until' => $validUntil,
                'subtotal' => rand(10000, 100000),
                'tax_amount' => 0,
                'total' => rand(10000, 100000),
                'currency' => 'ZMW',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $created[] = Quotation::find($id);
        }

        return $created;
    }

    private function createBills($organization, $vendors)
    {
        $statuses = ['pending', 'approved', 'paid'];
        $created = [];

        for ($i = 0; $i < 15; $i++) {
            $billNumber = 'BIL-' . str_pad($i + 1, 6, '0', STR_PAD_LEFT);
            
            // Check if bill already exists
            $existing = Bill::where('organization_id', $organization->id)
                ->where('bill_number', $billNumber)
                ->first();
            
            if ($existing) {
                continue; // Skip if already exists
            }
            
            $vendor = $vendors[array_rand($vendors)];
            $status = $statuses[array_rand($statuses)];
            $billDate = Carbon::now()->subDays(rand(1, 60));
            $dueDate = $billDate->copy()->addDays(rand(15, 45));
            $amount = rand(5000, 50000);

            // Use DB insert to bypass model boot method that tries to set fields not in table
            $id = (string) Str::uuid();
            \Illuminate\Support\Facades\DB::table('bills')->insert([
                'id' => $id,
                'organization_id' => $organization->id,
                'vendor_id' => $vendor->id,
                'bill_number' => $billNumber,
                'bill_date' => $billDate,
                'due_date' => $dueDate,
                'amount' => $amount,
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $bill = Bill::find($id);

            $created[] = $bill;
        }

        return $created;
    }

    private function createPayments($organization, $customers, $invoices)
    {
        $created = [];
        $paidInvoices = array_filter($invoices, fn($inv) => $inv->status === 'paid');

        foreach ($paidInvoices as $invoice) {
            $created[] = Payment::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'customer_id' => $invoice->customer_id,
                'payment_number' => 'PAY-' . str_pad(count($created) + 1, 6, '0', STR_PAD_LEFT),
                'payment_date' => $invoice->invoice_date->copy()->addDays(rand(1, 30)),
                'amount' => $invoice->total_amount,
                'currency' => 'ZMW',
                'payment_method' => ['cash', 'bank_transfer', 'mobile_money'][array_rand(['cash', 'bank_transfer', 'mobile_money'])],
            ]);
        }

        return $created;
    }

    private function createMoneyMovements($organization, $accounts)
    {
        $types = ['income', 'expense'];
        $categories = ['Sales', 'Services', 'Office Supplies', 'Utilities', 'Rent', 'Salaries'];
        $created = [];

        for ($i = 0; $i < 30; $i++) {
            $type = $types[array_rand($types)];
            $account = $accounts[array_rand($accounts)];
            $amount = rand(1000, 50000);
            $date = Carbon::now()->subDays(rand(1, 90));

            $created[] = MoneyMovement::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'from_account_id' => $type === 'expense' ? $account->id : null,
                'to_account_id' => $type === 'income' ? $account->id : null,
                'flow_type' => $type,
                'category' => $categories[array_rand($categories)],
                'amount' => $amount,
                'currency' => 'ZMW',
                'transaction_date' => $date,
                'status' => 'approved',
                'description' => ucfirst($type) . ' - ' . $categories[array_rand($categories)],
            ]);
        }

        return $created;
    }
}

