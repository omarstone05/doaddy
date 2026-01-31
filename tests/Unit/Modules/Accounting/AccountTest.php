<?php

namespace Tests\Unit\Modules\Accounting;

use Tests\TestCase;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\AccountType;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Models\JournalEntryLine;
use Illuminate\Support\Str;

class AccountTest extends TestCase
{
    protected AccountType $assetType;
    protected AccountType $liabilityType;

    protected function setUp(): void
    {
        parent::setUp();

        // Create account types
        $this->assetType = AccountType::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->testOrganization->id,
            'name' => 'Asset',
            'category' => 'asset',
            'is_active' => true,
        ]);

        $this->liabilityType = AccountType::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->testOrganization->id,
            'name' => 'Liability',
            'category' => 'liability',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_creates_account_with_required_fields(): void
    {
        $account = Account::create([
            'organization_id' => $this->testOrganization->id,
            'account_type_id' => $this->assetType->id,
            'code' => '1000',
            'name' => 'Cash',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        $this->assertNotNull($account->id);
        $this->assertEquals('Cash', $account->name);
        $this->assertEquals('1000', $account->code);
    }

    /** @test */
    public function it_belongs_to_account_type(): void
    {
        $account = Account::create([
            'organization_id' => $this->testOrganization->id,
            'account_type_id' => $this->assetType->id,
            'code' => '1000',
            'name' => 'Cash',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        $this->assertInstanceOf(AccountType::class, $account->accountType);
        $this->assertEquals('Asset', $account->accountType->name);
    }

    /** @test */
    public function it_can_have_parent_account(): void
    {
        $parentAccount = Account::create([
            'organization_id' => $this->testOrganization->id,
            'account_type_id' => $this->assetType->id,
            'code' => '1000',
            'name' => 'Current Assets',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        $childAccount = Account::create([
            'organization_id' => $this->testOrganization->id,
            'account_type_id' => $this->assetType->id,
            'parent_account_id' => $parentAccount->id,
            'code' => '1100',
            'name' => 'Cash',
            'normal_balance' => 'debit',
            'is_active' => true,
            'is_sub_account' => true,
        ]);

        $this->assertInstanceOf(Account::class, $childAccount->parentAccount);
        $this->assertEquals($parentAccount->id, $childAccount->parentAccount->id);
    }

    /** @test */
    public function it_has_many_sub_accounts(): void
    {
        $parentAccount = Account::create([
            'organization_id' => $this->testOrganization->id,
            'account_type_id' => $this->assetType->id,
            'code' => '1000',
            'name' => 'Current Assets',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        Account::create([
            'organization_id' => $this->testOrganization->id,
            'account_type_id' => $this->assetType->id,
            'parent_account_id' => $parentAccount->id,
            'code' => '1100',
            'name' => 'Cash',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        Account::create([
            'organization_id' => $this->testOrganization->id,
            'account_type_id' => $this->assetType->id,
            'parent_account_id' => $parentAccount->id,
            'code' => '1200',
            'name' => 'Bank',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        $this->assertCount(2, $parentAccount->subAccounts);
    }

    /** @test */
    public function it_generates_full_code_path(): void
    {
        $parentAccount = Account::create([
            'organization_id' => $this->testOrganization->id,
            'account_type_id' => $this->assetType->id,
            'code' => '1000',
            'name' => 'Current Assets',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        $childAccount = Account::create([
            'organization_id' => $this->testOrganization->id,
            'account_type_id' => $this->assetType->id,
            'parent_account_id' => $parentAccount->id,
            'code' => '1100',
            'name' => 'Cash',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        $this->assertEquals('1000.1100', $childAccount->full_code);
    }

    /** @test */
    public function it_generates_full_name_path(): void
    {
        $parentAccount = Account::create([
            'organization_id' => $this->testOrganization->id,
            'account_type_id' => $this->assetType->id,
            'code' => '1000',
            'name' => 'Current Assets',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        $childAccount = Account::create([
            'organization_id' => $this->testOrganization->id,
            'account_type_id' => $this->assetType->id,
            'parent_account_id' => $parentAccount->id,
            'code' => '1100',
            'name' => 'Cash',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        $this->assertEquals('Current Assets > Cash', $childAccount->full_name);
    }

    /** @test */
    public function it_scopes_active_accounts(): void
    {
        Account::create([
            'organization_id' => $this->testOrganization->id,
            'account_type_id' => $this->assetType->id,
            'code' => '1000',
            'name' => 'Active Account',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        Account::create([
            'organization_id' => $this->testOrganization->id,
            'account_type_id' => $this->assetType->id,
            'code' => '1001',
            'name' => 'Inactive Account',
            'normal_balance' => 'debit',
            'is_active' => false,
        ]);

        $activeAccounts = Account::where('organization_id', $this->testOrganization->id)
            ->active()
            ->get();

        $this->assertCount(1, $activeAccounts);
        $this->assertEquals('Active Account', $activeAccounts->first()->name);
    }

    /** @test */
    public function it_scopes_by_account_type(): void
    {
        Account::create([
            'organization_id' => $this->testOrganization->id,
            'account_type_id' => $this->assetType->id,
            'code' => '1000',
            'name' => 'Cash',
            'normal_balance' => 'debit',
            'is_active' => true,
            'account_type' => 'asset',
        ]);

        Account::create([
            'organization_id' => $this->testOrganization->id,
            'account_type_id' => $this->liabilityType->id,
            'code' => '2000',
            'name' => 'Accounts Payable',
            'normal_balance' => 'credit',
            'is_active' => true,
            'account_type' => 'liability',
        ]);

        $assetAccounts = Account::where('organization_id', $this->testOrganization->id)
            ->byType('asset')
            ->get();

        $this->assertCount(1, $assetAccounts);
        $this->assertEquals('Cash', $assetAccounts->first()->name);
    }

    /** @test */
    public function it_prevents_deleting_system_account(): void
    {
        $account = Account::create([
            'organization_id' => $this->testOrganization->id,
            'account_type_id' => $this->assetType->id,
            'code' => '1000',
            'name' => 'System Account',
            'normal_balance' => 'debit',
            'is_active' => true,
            'is_system_account' => true,
        ]);

        $this->assertFalse($account->canDelete());
    }

    /** @test */
    public function it_prevents_deleting_account_with_sub_accounts(): void
    {
        $parentAccount = Account::create([
            'organization_id' => $this->testOrganization->id,
            'account_type_id' => $this->assetType->id,
            'code' => '1000',
            'name' => 'Parent Account',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        Account::create([
            'organization_id' => $this->testOrganization->id,
            'account_type_id' => $this->assetType->id,
            'parent_account_id' => $parentAccount->id,
            'code' => '1100',
            'name' => 'Child Account',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        $this->assertFalse($parentAccount->canDelete());
    }

    /** @test */
    public function it_prevents_deleting_account_with_balance(): void
    {
        $account = Account::create([
            'organization_id' => $this->testOrganization->id,
            'account_type_id' => $this->assetType->id,
            'code' => '1000',
            'name' => 'Account With Balance',
            'normal_balance' => 'debit',
            'current_balance' => 1000,
            'is_active' => true,
        ]);

        $this->assertFalse($account->canDelete());
    }

    /** @test */
    public function it_allows_deleting_empty_non_system_account(): void
    {
        $account = Account::create([
            'organization_id' => $this->testOrganization->id,
            'account_type_id' => $this->assetType->id,
            'code' => '1000',
            'name' => 'Empty Account',
            'normal_balance' => 'debit',
            'current_balance' => 0,
            'is_active' => true,
            'is_system_account' => false,
        ]);

        $this->assertTrue($account->canDelete());
    }

    /** @test */
    public function it_casts_amounts_as_decimal(): void
    {
        $account = Account::create([
            'organization_id' => $this->testOrganization->id,
            'account_type_id' => $this->assetType->id,
            'code' => '1000',
            'name' => 'Cash',
            'normal_balance' => 'debit',
            'opening_balance' => 1000.50,
            'current_balance' => 1500.75,
            'is_active' => true,
        ]);

        $this->assertEquals('1000.50', $account->opening_balance);
        $this->assertEquals('1500.75', $account->current_balance);
    }

    /** @test */
    public function it_casts_metadata_as_array(): void
    {
        $account = Account::create([
            'organization_id' => $this->testOrganization->id,
            'account_type_id' => $this->assetType->id,
            'code' => '1000',
            'name' => 'Cash',
            'normal_balance' => 'debit',
            'is_active' => true,
            'metadata' => ['currency' => 'ZMW', 'bank_name' => 'FNB'],
        ]);

        $this->assertIsArray($account->metadata);
        $this->assertEquals('ZMW', $account->metadata['currency']);
    }

    /** @test */
    public function it_respects_organization_isolation(): void
    {
        $otherOrg = $this->createOtherOrganization();

        $otherAssetType = AccountType::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $otherOrg->id,
            'name' => 'Other Asset',
            'category' => 'asset',
            'is_active' => true,
        ]);

        Account::create([
            'organization_id' => $this->testOrganization->id,
            'account_type_id' => $this->assetType->id,
            'code' => '1000',
            'name' => 'Test Org Account',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        Account::create([
            'organization_id' => $otherOrg->id,
            'account_type_id' => $otherAssetType->id,
            'code' => '1000',
            'name' => 'Other Org Account',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        $testOrgAccounts = Account::withoutGlobalScope('organization')
            ->where('organization_id', $this->testOrganization->id)
            ->get();

        $this->assertCount(1, $testOrgAccounts);
        $this->assertEquals('Test Org Account', $testOrgAccounts->first()->name);
    }
}
