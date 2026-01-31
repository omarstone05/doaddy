<?php

namespace App\Modules\Accounting\Database\Seeders;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\AccountType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultChartOfAccountsSeeder extends Seeder
{
    protected ?string $organizationId = null;

    /**
     * Set the organization ID to seed accounts for
     */
    public function setOrganizationId(?string $organizationId): void
    {
        $this->organizationId = $organizationId;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get organization ID - use provided ID, or first organization
        $organizationId = $this->organizationId ?? \App\Models\Organization::first()?->id;

        if (!$organizationId) {
            if ($this->command) {
                $this->command->error('No organization found. Please create an organization first.');
            }
            \Log::error('DefaultChartOfAccountsSeeder: No organization found');
            return;
        }

        if ($this->command) {
            $this->command->info("Seeding default chart of accounts for organization: {$organizationId}");
        } else {
            \Log::info("Seeding default chart of accounts for organization: {$organizationId}");
        }

        DB::beginTransaction();
        try {
            // First, ensure account types exist
            $this->seedAccountTypes($organizationId);
            
            // Then seed accounts
            $this->seedAccounts($organizationId);
            
            DB::commit();
            if ($this->command) {
                $this->command->info('Default chart of accounts seeded successfully!');
            } else {
                \Log::info('Default chart of accounts seeded successfully!');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMsg = 'Failed to seed chart of accounts: ' . $e->getMessage();
            if ($this->command) {
                $this->command->error($errorMsg);
            }
            \Log::error($errorMsg, ['exception' => $e]);
            throw $e;
        }
    }

    protected function seedAccountTypes(string $organizationId): void
    {
        $defaults = AccountType::getDefaults();
        
        foreach ($defaults as $default) {
            // Check if account type already exists (code is unique globally)
            $existing = AccountType::where('code', $default['code'])->first();
            
            if ($existing) {
                // Update existing to ensure it has the correct organization_id and other fields
                $existing->update(array_merge($default, [
                    'organization_id' => $organizationId,
                    'report_category' => $this->getReportCategory($default['category']),
                ]));
            } else {
                // Create new
                AccountType::create(array_merge($default, [
                    'organization_id' => $organizationId,
                    'report_category' => $this->getReportCategory($default['category']),
                ]));
            }
        }
    }

    protected function getReportCategory(string $category): string
    {
        return match($category) {
            'asset', 'liability', 'equity' => 'balance_sheet',
            'revenue', 'expense' => 'profit_loss',
            default => 'profit_loss',
        };
    }

    protected function seedAccounts(string $organizationId): void
    {
        // Get account types
        $assetType = AccountType::where('organization_id', $organizationId)
            ->where('code', 'ASSET')->first();
        $liabType = AccountType::where('organization_id', $organizationId)
            ->where('code', 'LIAB')->first();
        $equityType = AccountType::where('organization_id', $organizationId)
            ->where('code', 'EQUITY')->first();
        $revType = AccountType::where('organization_id', $organizationId)
            ->where('code', 'REV')->first();
        $expType = AccountType::where('organization_id', $organizationId)
            ->where('code', 'EXP')->first();

        if (!$assetType || !$liabType || !$equityType || !$revType || !$expType) {
            throw new \Exception('Account types not found. Please seed account types first.');
        }

        $accounts = $this->getDefaultAccounts($assetType->id, $liabType->id, $equityType->id, $revType->id, $expType->id);
        
        foreach ($accounts as $accountData) {
            $parentId = null;
            $parentCode = $accountData['parent_code'] ?? null;
            
            // Remove parent_code from accountData as it's not a database column
            unset($accountData['parent_code']);
            
            if ($parentCode) {
                $parent = Account::where('organization_id', $organizationId)
                    ->where('code', $parentCode)
                    ->first();
                $parentId = $parent?->id;
            }

            Account::firstOrCreate(
                [
                    'organization_id' => $organizationId,
                    'code' => $accountData['code'],
                ],
                array_merge($accountData, [
                    'organization_id' => $organizationId,
                    'parent_account_id' => $parentId,
                    'is_system_account' => true,
                    'is_active' => true,
                ])
            );
        }
    }

    protected function getDefaultAccounts(string $assetTypeId, string $liabTypeId, string $equityTypeId, string $revTypeId, string $expTypeId): array
    {
        return [
            // ASSETS - Current Assets (1000-1999)
            ['code' => '1000', 'name' => 'Cash and Cash Equivalents', 'account_type_id' => $assetTypeId, 'account_type' => 'asset', 'normal_balance' => 'debit', 'description' => 'All cash accounts and cash equivalents'],
            ['code' => '1010', 'name' => 'Petty Cash', 'account_type_id' => $assetTypeId, 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1000', 'description' => 'Small cash fund for minor expenses'],
            ['code' => '1020', 'name' => 'Bank Account - Operating', 'account_type_id' => $assetTypeId, 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1000', 'description' => 'Primary operating bank account'],
            ['code' => '1030', 'name' => 'Bank Account - Savings', 'account_type_id' => $assetTypeId, 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1000', 'description' => 'Savings bank account'],
            ['code' => '1100', 'name' => 'Accounts Receivable', 'account_type_id' => $assetTypeId, 'account_type' => 'asset', 'normal_balance' => 'debit', 'description' => 'Amounts owed by customers'],
            ['code' => '1200', 'name' => 'Inventory', 'account_type_id' => $assetTypeId, 'account_type' => 'asset', 'normal_balance' => 'debit', 'description' => 'Goods held for sale'],
            ['code' => '1300', 'name' => 'Prepaid Expenses', 'account_type_id' => $assetTypeId, 'account_type' => 'asset', 'normal_balance' => 'debit', 'description' => 'Expenses paid in advance'],
            ['code' => '1310', 'name' => 'Prepaid Insurance', 'account_type_id' => $assetTypeId, 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1300', 'description' => 'Insurance premiums paid in advance'],
            ['code' => '1320', 'name' => 'Prepaid Rent', 'account_type_id' => $assetTypeId, 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1300', 'description' => 'Rent paid in advance'],

            // ASSETS - Fixed Assets (2000-2999)
            ['code' => '2000', 'name' => 'Property, Plant & Equipment', 'account_type_id' => $assetTypeId, 'account_type' => 'asset', 'normal_balance' => 'debit', 'description' => 'Long-term tangible assets'],
            ['code' => '2100', 'name' => 'Land', 'account_type_id' => $assetTypeId, 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '2000', 'description' => 'Land owned by the business'],
            ['code' => '2200', 'name' => 'Buildings', 'account_type_id' => $assetTypeId, 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '2000', 'description' => 'Buildings and structures'],
            ['code' => '2210', 'name' => 'Accumulated Depreciation - Buildings', 'account_type_id' => $assetTypeId, 'account_type' => 'asset', 'normal_balance' => 'credit', 'parent_code' => '2200', 'description' => 'Contra-asset: accumulated depreciation on buildings'],
            ['code' => '2300', 'name' => 'Furniture & Fixtures', 'account_type_id' => $assetTypeId, 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '2000', 'description' => 'Office furniture and fixtures'],
            ['code' => '2310', 'name' => 'Accumulated Depreciation - Furniture', 'account_type_id' => $assetTypeId, 'account_type' => 'asset', 'normal_balance' => 'credit', 'parent_code' => '2300', 'description' => 'Contra-asset: accumulated depreciation on furniture'],
            ['code' => '2400', 'name' => 'Equipment', 'account_type_id' => $assetTypeId, 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '2000', 'description' => 'Business equipment'],
            ['code' => '2410', 'name' => 'Accumulated Depreciation - Equipment', 'account_type_id' => $assetTypeId, 'account_type' => 'asset', 'normal_balance' => 'credit', 'parent_code' => '2400', 'description' => 'Contra-asset: accumulated depreciation on equipment'],
            ['code' => '2500', 'name' => 'Vehicles', 'account_type_id' => $assetTypeId, 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '2000', 'description' => 'Company vehicles'],
            ['code' => '2510', 'name' => 'Accumulated Depreciation - Vehicles', 'account_type_id' => $assetTypeId, 'account_type' => 'asset', 'normal_balance' => 'credit', 'parent_code' => '2500', 'description' => 'Contra-asset: accumulated depreciation on vehicles'],
            ['code' => '2600', 'name' => 'Computer Equipment', 'account_type_id' => $assetTypeId, 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '2000', 'description' => 'Computers and IT equipment'],
            ['code' => '2610', 'name' => 'Accumulated Depreciation - Computer Equipment', 'account_type_id' => $assetTypeId, 'account_type' => 'asset', 'normal_balance' => 'credit', 'parent_code' => '2600', 'description' => 'Contra-asset: accumulated depreciation on computer equipment'],

            // ASSETS - Other Assets (3000-3999)
            ['code' => '3000', 'name' => 'Intangible Assets', 'account_type_id' => $assetTypeId, 'account_type' => 'asset', 'normal_balance' => 'debit', 'description' => 'Non-physical assets'],
            ['code' => '3100', 'name' => 'Goodwill', 'account_type_id' => $assetTypeId, 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '3000', 'description' => 'Goodwill from business acquisitions'],
            ['code' => '3200', 'name' => 'Patents & Trademarks', 'account_type_id' => $assetTypeId, 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '3000', 'description' => 'Intellectual property assets'],

            // LIABILITIES - Current Liabilities (4000-4999)
            ['code' => '4000', 'name' => 'Accounts Payable', 'account_type_id' => $liabTypeId, 'account_type' => 'liability', 'normal_balance' => 'credit', 'description' => 'Amounts owed to suppliers'],
            ['code' => '4100', 'name' => 'Credit Cards Payable', 'account_type_id' => $liabTypeId, 'account_type' => 'liability', 'normal_balance' => 'credit', 'description' => 'Outstanding credit card balances'],
            ['code' => '4200', 'name' => 'Sales Tax Payable (VAT/GST)', 'account_type_id' => $liabTypeId, 'account_type' => 'liability', 'normal_balance' => 'credit', 'description' => 'Sales tax collected and owed'],
            ['code' => '4210', 'name' => 'Withholding Tax Payable', 'account_type_id' => $liabTypeId, 'account_type' => 'liability', 'normal_balance' => 'credit', 'description' => 'Withholding tax owed'],
            ['code' => '4300', 'name' => 'Accrued Expenses', 'account_type_id' => $liabTypeId, 'account_type' => 'liability', 'normal_balance' => 'credit', 'description' => 'Expenses incurred but not yet paid'],
            ['code' => '4310', 'name' => 'Accrued Salaries & Wages', 'account_type_id' => $liabTypeId, 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '4300', 'description' => 'Accrued payroll expenses'],
            ['code' => '4400', 'name' => 'Short-term Loans', 'account_type_id' => $liabTypeId, 'account_type' => 'liability', 'normal_balance' => 'credit', 'description' => 'Short-term borrowings'],
            ['code' => '4500', 'name' => 'Customer Deposits', 'account_type_id' => $liabTypeId, 'account_type' => 'liability', 'normal_balance' => 'credit', 'description' => 'Deposits received from customers'],
            ['code' => '4600', 'name' => 'Unearned Revenue', 'account_type_id' => $liabTypeId, 'account_type' => 'liability', 'normal_balance' => 'credit', 'description' => 'Revenue received but not yet earned'],

            // LIABILITIES - Long-term Liabilities (5000-5999)
            ['code' => '5000', 'name' => 'Long-term Loans', 'account_type_id' => $liabTypeId, 'account_type' => 'liability', 'normal_balance' => 'credit', 'description' => 'Long-term borrowings'],
            ['code' => '5100', 'name' => 'Mortgage Payable', 'account_type_id' => $liabTypeId, 'account_type' => 'liability', 'normal_balance' => 'credit', 'description' => 'Mortgage obligations'],
            ['code' => '5200', 'name' => 'Notes Payable', 'account_type_id' => $liabTypeId, 'account_type' => 'liability', 'normal_balance' => 'credit', 'description' => 'Promissory notes payable'],

            // EQUITY (6000-6999)
            ['code' => '6000', 'name' => 'Owner\'s Equity / Share Capital', 'account_type_id' => $equityTypeId, 'account_type' => 'equity', 'normal_balance' => 'credit', 'description' => 'Owner\'s initial investment'],
            ['code' => '6100', 'name' => 'Retained Earnings', 'account_type_id' => $equityTypeId, 'account_type' => 'equity', 'normal_balance' => 'credit', 'description' => 'Accumulated profits from previous years'],
            ['code' => '6200', 'name' => 'Owner\'s Drawings', 'account_type_id' => $equityTypeId, 'account_type' => 'equity', 'normal_balance' => 'debit', 'description' => 'Owner withdrawals'],
            ['code' => '6300', 'name' => 'Current Year Earnings', 'account_type_id' => $equityTypeId, 'account_type' => 'equity', 'normal_balance' => 'credit', 'description' => 'Net income for current year (auto-calculated)'],

            // INCOME - Revenue (7000-7999)
            ['code' => '7000', 'name' => 'Sales Revenue', 'account_type_id' => $revTypeId, 'account_type' => 'income', 'normal_balance' => 'credit', 'description' => 'Revenue from product sales'],
            ['code' => '7100', 'name' => 'Consulting Income', 'account_type_id' => $revTypeId, 'account_type' => 'income', 'normal_balance' => 'credit', 'description' => 'Revenue from consulting services'],
            ['code' => '7200', 'name' => 'Service Revenue', 'account_type_id' => $revTypeId, 'account_type' => 'income', 'normal_balance' => 'credit', 'description' => 'Revenue from services'],
            ['code' => '7300', 'name' => 'Product Sales', 'account_type_id' => $revTypeId, 'account_type' => 'income', 'normal_balance' => 'credit', 'parent_code' => '7000', 'description' => 'Revenue from product sales'],
            ['code' => '7400', 'name' => 'Sales Discounts', 'account_type_id' => $revTypeId, 'account_type' => 'income', 'normal_balance' => 'debit', 'parent_code' => '7000', 'description' => 'Contra-revenue: discounts given on sales'],
            ['code' => '7500', 'name' => 'Sales Returns & Allowances', 'account_type_id' => $revTypeId, 'account_type' => 'income', 'normal_balance' => 'debit', 'parent_code' => '7000', 'description' => 'Contra-revenue: returns and allowances'],

            // INCOME - Other Income (8000-8999)
            ['code' => '8000', 'name' => 'Interest Income', 'account_type_id' => $revTypeId, 'account_type' => 'other_income', 'normal_balance' => 'credit', 'description' => 'Interest earned on investments'],
            ['code' => '8100', 'name' => 'Dividend Income', 'account_type_id' => $revTypeId, 'account_type' => 'other_income', 'normal_balance' => 'credit', 'description' => 'Dividends received'],
            ['code' => '8200', 'name' => 'Rental Income', 'account_type_id' => $revTypeId, 'account_type' => 'other_income', 'normal_balance' => 'credit', 'description' => 'Income from property rental'],
            ['code' => '8300', 'name' => 'Gain on Sale of Assets', 'account_type_id' => $revTypeId, 'account_type' => 'other_income', 'normal_balance' => 'credit', 'description' => 'Gains from asset sales'],
            ['code' => '8400', 'name' => 'Foreign Exchange Gains', 'account_type_id' => $revTypeId, 'account_type' => 'other_income', 'normal_balance' => 'credit', 'description' => 'FX gains'],

            // COST OF GOODS SOLD (9000-9999)
            ['code' => '9000', 'name' => 'Cost of Goods Sold', 'account_type_id' => $expTypeId, 'account_type' => 'cost_of_goods_sold', 'normal_balance' => 'debit', 'description' => 'Direct costs of products sold'],
            ['code' => '9100', 'name' => 'Purchases', 'account_type_id' => $expTypeId, 'account_type' => 'cost_of_goods_sold', 'normal_balance' => 'debit', 'parent_code' => '9000', 'description' => 'Cost of inventory purchases'],
            ['code' => '9200', 'name' => 'Freight & Shipping Costs', 'account_type_id' => $expTypeId, 'account_type' => 'cost_of_goods_sold', 'normal_balance' => 'debit', 'parent_code' => '9000', 'description' => 'Shipping and freight costs'],
            ['code' => '9300', 'name' => 'Purchase Discounts', 'account_type_id' => $expTypeId, 'account_type' => 'cost_of_goods_sold', 'normal_balance' => 'credit', 'parent_code' => '9000', 'description' => 'Contra-COGS: discounts received on purchases'],
            ['code' => '9400', 'name' => 'Purchase Returns', 'account_type_id' => $expTypeId, 'account_type' => 'cost_of_goods_sold', 'normal_balance' => 'credit', 'parent_code' => '9000', 'description' => 'Contra-COGS: purchase returns'],

            // EXPENSES - Operating Expenses (10000-19999)
            ['code' => '10000', 'name' => 'Salaries & Wages', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Employee salaries and wages'],
            ['code' => '10100', 'name' => 'Employee Benefits', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Employee benefits costs'],
            ['code' => '10200', 'name' => 'Payroll Taxes', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Payroll tax expenses'],
            ['code' => '10300', 'name' => 'Rent Expense', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Rental expenses'],
            ['code' => '10400', 'name' => 'Utilities', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Utility expenses'],
            ['code' => '10410', 'name' => 'Electricity', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '10400', 'description' => 'Electricity costs'],
            ['code' => '10420', 'name' => 'Water', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '10400', 'description' => 'Water costs'],
            ['code' => '10430', 'name' => 'Internet & Phone', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '10400', 'description' => 'Internet and phone expenses'],
            ['code' => '10500', 'name' => 'Office Supplies', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Office supply expenses'],
            ['code' => '10600', 'name' => 'Insurance Expense', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Insurance costs'],
            ['code' => '10700', 'name' => 'Depreciation Expense', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Depreciation charges'],
            ['code' => '10800', 'name' => 'Repairs & Maintenance', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Repair and maintenance costs'],
            ['code' => '10900', 'name' => 'Professional Fees', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Professional service fees'],
            ['code' => '10910', 'name' => 'Legal Fees', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '10900', 'description' => 'Legal service fees'],
            ['code' => '10920', 'name' => 'Accounting Fees', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '10900', 'description' => 'Accounting service fees'],
            ['code' => '10930', 'name' => 'Consulting Fees', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '10900', 'description' => 'Consulting service fees'],
            ['code' => '11000', 'name' => 'Advertising & Marketing', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Marketing and advertising costs'],
            ['code' => '11100', 'name' => 'Travel & Entertainment', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Travel and entertainment expenses'],
            ['code' => '11200', 'name' => 'Vehicle Expenses', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Vehicle operating costs'],
            ['code' => '11300', 'name' => 'Bank Fees & Charges', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Banking fees'],
            ['code' => '11400', 'name' => 'Interest Expense', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Interest on loans'],
            ['code' => '11500', 'name' => 'Bad Debt Expense', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Uncollectible accounts'],
            ['code' => '11600', 'name' => 'Licenses & Permits', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'License and permit costs'],
            ['code' => '11700', 'name' => 'Training & Development', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Employee training costs'],
            ['code' => '11800', 'name' => 'Software & Subscriptions', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Software and subscription costs'],
            ['code' => '11900', 'name' => 'Miscellaneous Expenses', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Other miscellaneous expenses'],

            // EXPENSES - Other Expenses (20000-29999)
            ['code' => '20000', 'name' => 'Loss on Sale of Assets', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Losses from asset sales'],
            ['code' => '20100', 'name' => 'Foreign Exchange Losses', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'FX losses'],
            ['code' => '20200', 'name' => 'Income Tax Expense', 'account_type_id' => $expTypeId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Income tax expenses'],
        ];
    }
}

