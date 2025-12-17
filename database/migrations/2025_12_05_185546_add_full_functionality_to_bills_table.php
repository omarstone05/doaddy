<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            // Check if columns already exist before adding
            // Note: created_by already exists as UUID in original migration, so we skip it
            if (!Schema::hasColumn('bills', 'approved_by')) {
                $table->uuid('approved_by')->nullable()->after('created_by');
                $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('bills', 'vendor_invoice_number')) {
                $table->string('vendor_invoice_number')->nullable()->after('bill_number');
            }
            if (!Schema::hasColumn('bills', 'payment_status')) {
                $table->enum('payment_status', ['unpaid', 'partially_paid', 'paid'])->default('unpaid')->after('status');
            }
            if (!Schema::hasColumn('bills', 'received_date')) {
                $table->date('received_date')->nullable()->after('due_date');
            }
            if (!Schema::hasColumn('bills', 'approved_date')) {
                $table->date('approved_date')->nullable()->after('received_date');
            }
            if (!Schema::hasColumn('bills', 'subtotal')) {
                $table->decimal('subtotal', 15, 2)->default(0)->after('amount');
            }
            if (!Schema::hasColumn('bills', 'discount_amount')) {
                $table->decimal('discount_amount', 15, 2)->default(0)->after('subtotal');
            }
            if (!Schema::hasColumn('bills', 'tax_amount')) {
                $table->decimal('tax_amount', 15, 2)->default(0)->after('discount_amount');
            }
            if (!Schema::hasColumn('bills', 'total')) {
                $table->decimal('total', 15, 2)->default(0)->after('tax_amount');
            }
            if (!Schema::hasColumn('bills', 'amount_paid')) {
                $table->decimal('amount_paid', 15, 2)->default(0)->after('total');
            }
            if (!Schema::hasColumn('bills', 'amount_due')) {
                $table->decimal('amount_due', 15, 2)->default(0)->after('amount_paid');
            }
            if (!Schema::hasColumn('bills', 'currency')) {
                $table->string('currency', 3)->default('ZMW')->after('amount_due');
            }
            if (!Schema::hasColumn('bills', 'category')) {
                $table->string('category')->nullable()->after('currency');
            }
            if (!Schema::hasColumn('bills', 'department')) {
                $table->string('department')->nullable()->after('category');
            }
            if (!Schema::hasColumn('bills', 'project')) {
                $table->string('project')->nullable()->after('department');
            }
            if (!Schema::hasColumn('bills', 'payment_method')) {
                $table->enum('payment_method', ['cash', 'bank_transfer', 'mobile_money', 'check', 'credit_card', 'other'])->nullable()->after('project');
            }
            if (!Schema::hasColumn('bills', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('bills', 'is_recurring')) {
                $table->boolean('is_recurring')->default(false)->after('payment_reference');
            }
            if (!Schema::hasColumn('bills', 'recurring_frequency')) {
                $table->enum('recurring_frequency', ['weekly', 'monthly', 'quarterly', 'yearly'])->nullable()->after('is_recurring');
            }
            if (!Schema::hasColumn('bills', 'next_recurrence_date')) {
                $table->date('next_recurrence_date')->nullable()->after('recurring_frequency');
            }
            if (!Schema::hasColumn('bills', 'description')) {
                $table->text('description')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('bills', 'internal_notes')) {
                $table->text('internal_notes')->nullable()->after('description');
            }
            if (!Schema::hasColumn('bills', 'attachments')) {
                $table->json('attachments')->nullable()->after('internal_notes');
            }
            if (!Schema::hasColumn('bills', 'tags')) {
                $table->json('tags')->nullable()->after('attachments');
            }
            
            // Add indexes only for non-FK columns that may not have indexes
            // Use raw SQL to check if index exists before adding
            $connection = Schema::getConnection();
            
            $driver = $connection->getDriverName();

            if ($driver !== 'sqlite') {
                if (Schema::hasColumn('bills', 'payment_status')) {
                    $indexExists = $connection->selectOne("
                        SELECT COUNT(*) as count 
                        FROM information_schema.statistics 
                        WHERE table_schema = DATABASE() 
                        AND table_name = 'bills' 
                        AND index_name = 'bills_payment_status_index'
                    ");
                    if ($indexExists && $indexExists->count == 0) {
                        $table->index('payment_status');
                    }
                }
                
                if (Schema::hasColumn('bills', 'category')) {
                    $indexExists = $connection->selectOne("
                        SELECT COUNT(*) as count 
                        FROM information_schema.statistics 
                        WHERE table_schema = DATABASE() 
                        AND table_name = 'bills' 
                        AND index_name = 'bills_category_index'
                    ");
                    if ($indexExists && $indexExists->count == 0) {
                        $table->index('category');
                    }
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $columnsToDrop = [
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
            ];
            
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('bills', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
