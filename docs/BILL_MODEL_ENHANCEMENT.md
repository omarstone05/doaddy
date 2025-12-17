# Bill Model Enhancement Guide

## Current State

The `Bill` model has been simplified to match the current database table structure. The table currently has these columns:

- `id` (UUID)
- `organization_id` (integer)
- `vendor_id` (UUID)
- `bill_number` (string)
- `bill_date` (date)
- `due_date` (date)
- `amount` (decimal)
- `status` (string, default: 'pending')
- `notes` (text)
- `created_at`, `updated_at`, `deleted_at`

## Removed Functionality

The following features were removed because they depend on columns that don't exist in the current table:

### Missing Columns
- `created_by` - User who created the bill
- `vendor_invoice_number` - Reference number from vendor
- `payment_status` - Separate payment tracking (unpaid, partially_paid, paid)
- `received_date` - When the bill was received
- `approved_date` - When the bill was approved
- `approved_by` - User who approved the bill
- `subtotal` - Amount before tax/discount
- `discount_amount` - Discount applied
- `tax_amount` - Tax amount
- `total` - Final total amount
- `amount_paid` - Amount already paid
- `amount_due` - Remaining amount due
- `currency` - Currency code
- `category` - Bill category
- `department` - Department responsible
- `project` - Associated project
- `payment_method` - How payment was made
- `payment_reference` - Payment reference number
- `is_recurring` - Whether bill recurs
- `recurring_frequency` - Recurrence frequency (weekly, monthly, etc.)
- `next_recurrence_date` - Next occurrence date
- `attachments` - JSON array of attachments
- `description` - Detailed description
- `internal_notes` - Internal notes
- `tags` - JSON array of tags

### Removed Methods
- `calculateTotals()` - Calculates subtotal, tax, total, and amount due
- `updatePaymentStatus()` - Updates payment status based on amounts
- `recordPayment()` - Records a payment and updates bill status
- `approve()` - Approves a bill with user tracking
- `generateNextRecurrence()` - Creates next recurring bill

### Removed Relationships
- `creator()` - Relationship to User who created the bill
- `approver()` - Relationship to User who approved the bill
- `items()` - Relationship to BillItem (line items)
- `payments()` - Relationship to BillPayment (payment records)

## Steps to Restore Full Functionality

### Step 1: Create Migration for Additional Columns

Create a new migration file:

```bash
php artisan make:migration add_full_functionality_to_bills_table
```

Add the following columns to the migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            // User tracking
            $table->foreignId('created_by')->nullable()->after('vendor_id')->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            
            // Vendor reference
            $table->string('vendor_invoice_number')->nullable()->after('bill_number');
            
            // Payment tracking
            $table->enum('payment_status', ['unpaid', 'partially_paid', 'paid'])->default('unpaid')->after('status');
            
            // Dates
            $table->date('received_date')->nullable()->after('due_date');
            $table->date('approved_date')->nullable()->after('received_date');
            
            // Financial breakdown
            $table->decimal('subtotal', 15, 2)->default(0)->after('amount');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('subtotal');
            $table->decimal('tax_amount', 15, 2)->default(0)->after('discount_amount');
            $table->decimal('total', 15, 2)->default(0)->after('tax_amount');
            $table->decimal('amount_paid', 15, 2)->default(0)->after('total');
            $table->decimal('amount_due', 15, 2)->default(0)->after('amount_paid');
            
            // Additional fields
            $table->string('currency', 3)->default('ZMW')->after('amount_due');
            $table->string('category')->nullable()->after('currency');
            $table->string('department')->nullable()->after('category');
            $table->string('project')->nullable()->after('department');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'mobile_money', 'check', 'credit_card', 'other'])->nullable()->after('project');
            $table->string('payment_reference')->nullable()->after('payment_method');
            
            // Recurring bills
            $table->boolean('is_recurring')->default(false)->after('payment_reference');
            $table->enum('recurring_frequency', ['weekly', 'monthly', 'quarterly', 'yearly'])->nullable()->after('is_recurring');
            $table->date('next_recurrence_date')->nullable()->after('recurring_frequency');
            
            // Additional notes and metadata
            $table->text('description')->nullable()->after('notes');
            $table->text('internal_notes')->nullable()->after('description');
            $table->json('attachments')->nullable()->after('internal_notes');
            $table->json('tags')->nullable()->after('attachments');
            
            // Indexes
            $table->index('created_by');
            $table->index('approved_by');
            $table->index('payment_status');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'created_by',
                'approved_by',
                'vendor_invoice_number',
                'payment_status',
                'received_date',
                'approved_date',
                'subtotal',
                'discount_amount',
                'tax_amount',
                'total',
                'amount_paid',
                'amount_due',
                'currency',
                'category',
                'department',
                'project',
                'payment_method',
                'payment_reference',
                'is_recurring',
                'recurring_frequency',
                'next_recurrence_date',
                'description',
                'internal_notes',
                'attachments',
                'tags',
            ]);
        });
    }
};
```

### Step 2: Create BillItem Table (if needed)

If you want to track line items for bills:

```bash
php artisan make:migration create_bill_items_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('bill_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('goods_service_id')->nullable()->constrained('goods_and_services')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total', 15, 2);
            $table->integer('display_order')->default(0);
            $table->timestamps();
            
            $table->index(['bill_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_items');
    }
};
```

### Step 3: Create BillPayment Table (if needed)

If you want to track individual payments:

```bash
php artisan make:migration create_bill_payments_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('bill_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('money_account_id')->nullable()->constrained('money_accounts')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('ZMW');
            $table->date('payment_date');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'mobile_money', 'check', 'credit_card', 'other']);
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['bill_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_payments');
    }
};
```

### Step 4: Update Bill Model

After running the migrations, restore the full functionality in `app/Models/Bill.php`:

1. **Add all fields back to `$fillable`**
2. **Add all casts back to `$casts`**
3. **Restore the boot method** to calculate `amount_due` on creation
4. **Restore all relationships**: `creator()`, `approver()`, `items()`, `payments()`
5. **Restore all methods**: `calculateTotals()`, `updatePaymentStatus()`, `recordPayment()`, `approve()`, `generateNextRecurrence()`
6. **Update scopes** to use `payment_status` instead of `status` where appropriate

### Step 5: Create BillItem Model (if needed)

```bash
php artisan make:model BillItem
```

```php
<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillItem extends Model
{
    use HasUuid, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'bill_id',
        'goods_service_id',
        'name',
        'description',
        'quantity',
        'unit_price',
        'total',
        'display_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function goodsService(): BelongsTo
    {
        return $this->belongsTo(GoodsAndService::class, 'goods_service_id');
    }
}
```

### Step 6: Create BillPayment Model (if needed)

```bash
php artisan make:model BillPayment
```

```php
<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillPayment extends Model
{
    use HasUuid, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'bill_id',
        'vendor_id',
        'created_by',
        'money_account_id',
        'amount',
        'currency',
        'payment_date',
        'payment_method',
        'payment_reference',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function moneyAccount(): BelongsTo
    {
        return $this->belongsTo(MoneyAccount::class);
    }
}
```

### Step 7: Update Vendor Model (if needed)

If the `Vendor` model has an `updateFinancialMetrics()` method that's called from `Bill::recordPayment()`, ensure it exists and works correctly.

## Testing

After implementing the changes:

1. Run migrations: `php artisan migrate`
2. Test bill creation with all fields
3. Test `calculateTotals()` method
4. Test `recordPayment()` method
5. Test `approve()` method
6. Test recurring bills functionality
7. Test bill items relationships
8. Test bill payments relationships

## Notes

- The current simplified model works for basic bill tracking
- Full functionality requires the additional columns and related tables
- Consider your business needs before adding all features - you may not need everything
- Start with the most critical features (payment tracking, approval workflow) and add others as needed

