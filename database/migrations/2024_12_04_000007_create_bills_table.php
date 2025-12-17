<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bills')) {
            // Table exists, add missing columns if any
            return;
        }

        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->uuid('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->unsignedBigInteger('vendor_id');
            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
            $table->uuid('created_by');
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
            $table->string('bill_number')->unique();
            $table->string('vendor_invoice_number')->nullable();
            
            // Status
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'partially_paid', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->enum('payment_status', ['unpaid', 'partially_paid', 'paid'])->default('unpaid');
            
            // Dates
            $table->date('bill_date');
            $table->date('due_date');
            $table->date('received_date')->nullable();
            $table->date('approved_date')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            
            // Financial
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('amount_due', 15, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            
            // Categories
            $table->enum('category', ['inventory', 'services', 'utilities', 'rent', 'equipment', 'supplies', 'professional_fees', 'other'])->nullable();
            $table->string('department')->nullable();
            $table->string('project')->nullable();
            
            // Payment Information
            $table->enum('payment_method', ['bank_transfer', 'check', 'cash', 'mobile_money', 'credit_card', 'other'])->nullable();
            $table->string('payment_reference')->nullable();
            
            // Recurring
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurring_frequency', ['weekly', 'monthly', 'quarterly', 'yearly'])->nullable();
            $table->date('next_recurrence_date')->nullable();
            
            // Attachments
            $table->json('attachments')->nullable();
            
            // Notes
            $table->text('description')->nullable();
            $table->text('internal_notes')->nullable();
            $table->json('tags')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
            $table->index(['bill_number']);
            $table->index(['vendor_id']);
            $table->index(['due_date']);
            $table->index(['payment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
