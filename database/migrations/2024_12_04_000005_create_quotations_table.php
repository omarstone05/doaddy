<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quotations')) {
            // Table exists, alter customer_id to UUID if needed
            if (Schema::hasColumn('quotations', 'customer_id')) {
                // Check if foreign key exists and drop it
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'quotations' 
                    AND COLUMN_NAME = 'customer_id' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                if (!empty($foreignKeys)) {
                    Schema::table('quotations', function (Blueprint $table) {
                        $table->dropForeign([$foreignKeys[0]->CONSTRAINT_NAME]);
                    });
                }
                
                // Change column type from bigint to char(36) for UUID
                DB::statement('ALTER TABLE quotations MODIFY customer_id CHAR(36) NULL');
                
                // Re-add foreign key
                Schema::table('quotations', function (Blueprint $table) {
                    $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
                });
            } else {
                Schema::table('quotations', function (Blueprint $table) {
                    $table->uuid('customer_id')->nullable();
                    $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
                });
            }
            return;
        }

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->uuid('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->unsignedBigInteger('prospect_id')->nullable();
            $table->foreign('prospect_id')->references('id')->on('prospects')->nullOnDelete();
            $table->uuid('customer_id')->nullable();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->uuid('created_by');
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
            $table->string('quotation_number')->unique();
            
            // Basic Information
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'sent', 'viewed', 'accepted', 'rejected', 'expired'])->default('draft');
            
            // Dates
            $table->date('issue_date');
            $table->date('valid_until');
            $table->date('sent_at')->nullable();
            $table->date('viewed_at')->nullable();
            $table->date('responded_at')->nullable();
            
            // Financial
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            
            // Terms
            $table->text('terms_and_conditions')->nullable();
            $table->text('payment_terms')->nullable();
            $table->text('delivery_terms')->nullable();
            $table->integer('validity_days')->default(30);
            
            // Conversion
            $table->unsignedBigInteger('converted_to_invoice_id')->nullable();
            $table->foreign('converted_to_invoice_id')->references('id')->on('invoices')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->decimal('conversion_probability', 5, 2)->nullable(); // 0-100
            
            // Rejection
            $table->text('rejection_reason')->nullable();
            
            // Follow-up
            $table->timestamp('follow_up_date')->nullable();
            $table->boolean('follow_up_completed')->default(false);
            
            // Notes
            $table->text('internal_notes')->nullable();
            $table->json('tags')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
            $table->index(['quotation_number']);
            $table->index(['prospect_id']);
            $table->index(['customer_id']);
            $table->index(['valid_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
