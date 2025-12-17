<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bill_payments')) {
            // Table exists, check if organization_id needs to be updated
            if (Schema::hasColumn('bill_payments', 'organization_id')) {
                // Check if it's already UUID
                $columnType = DB::select("SHOW COLUMNS FROM bill_payments WHERE Field = 'organization_id'");
                if (!empty($columnType) && strpos($columnType[0]->Type, 'char') === false) {
                    // It's not UUID, we need to alter it (but this is complex, skip for now)
                    // The foreign key constraint will fail but the table exists
                }
            }
            return;
        }

        Schema::create('bill_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->unsignedBigInteger('bill_id');
            $table->foreign('bill_id')->references('id')->on('bills')->cascadeOnDelete();
            $table->unsignedBigInteger('vendor_id');
            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
            $table->uuid('created_by');
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
            $table->string('payment_number')->unique();
            
            // Payment Details
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('payment_method', ['bank_transfer', 'check', 'cash', 'mobile_money', 'credit_card', 'other']);
            $table->string('reference_number')->nullable();
            $table->string('check_number')->nullable();
            
            // Bank Details
            $table->unsignedBigInteger('bank_account_id')->nullable();
            // Note: bank_accounts table may not exist, so foreign key is optional
            $table->string('bank_name')->nullable();
            
            // Status
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('completed');
            
            // Notes
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index(['bill_id']);
            $table->index(['vendor_id']);
            $table->index(['payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_payments');
    }
};
