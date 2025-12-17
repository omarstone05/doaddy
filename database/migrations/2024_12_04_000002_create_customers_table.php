<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check if table exists, if so, alter it; otherwise create it
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                // Add new columns if they don't exist
                if (!Schema::hasColumn('customers', 'organization_id')) {
                    $table->uuid('organization_id')->after('id');
                    $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
                }
                if (!Schema::hasColumn('customers', 'customer_persona_id')) {
                    $table->unsignedBigInteger('customer_persona_id')->nullable()->after('organization_id');
                    $table->foreign('customer_persona_id')->references('id')->on('customer_personas')->onDelete('set null');
                }
                if (!Schema::hasColumn('customers', 'customer_code')) {
                    $table->string('customer_code')->nullable()->after('customer_persona_id');
                }
                if (!Schema::hasColumn('customers', 'type')) {
                    $table->enum('type', ['individual', 'business'])->default('business')->after('customer_code');
                }
                if (!Schema::hasColumn('customers', 'website')) {
                    $table->string('website')->nullable()->after('phone');
                }
                if (!Schema::hasColumn('customers', 'billing_address')) {
                    $table->text('billing_address')->nullable()->after('address');
                }
                if (!Schema::hasColumn('customers', 'shipping_address')) {
                    $table->text('shipping_address')->nullable()->after('billing_address');
                }
                if (!Schema::hasColumn('customers', 'city')) {
                    $table->string('city')->nullable()->after('shipping_address');
                }
                if (!Schema::hasColumn('customers', 'state')) {
                    $table->string('state')->nullable()->after('city');
                }
                if (!Schema::hasColumn('customers', 'country')) {
                    $table->string('country')->nullable()->after('state');
                }
                if (!Schema::hasColumn('customers', 'postal_code')) {
                    $table->string('postal_code')->nullable()->after('country');
                }
                if (!Schema::hasColumn('customers', 'credit_limit')) {
                    $table->decimal('credit_limit', 15, 2)->default(0)->after('postal_code');
                }
                if (!Schema::hasColumn('customers', 'payment_terms')) {
                    $table->enum('payment_terms', ['immediate', 'net_15', 'net_30', 'net_60', 'net_90', 'custom'])->default('net_30')->after('credit_limit');
                }
                if (!Schema::hasColumn('customers', 'custom_payment_days')) {
                    $table->integer('custom_payment_days')->nullable()->after('payment_terms');
                }
                if (!Schema::hasColumn('customers', 'currency')) {
                    $table->string('currency', 3)->default('USD')->after('custom_payment_days');
                }
                if (!Schema::hasColumn('customers', 'lifetime_value')) {
                    $table->decimal('lifetime_value', 15, 2)->default(0)->after('currency');
                }
                if (!Schema::hasColumn('customers', 'outstanding_balance')) {
                    $table->decimal('outstanding_balance', 15, 2)->default(0)->after('lifetime_value');
                }
                if (!Schema::hasColumn('customers', 'status')) {
                    $table->enum('status', ['active', 'inactive', 'blocked'])->default('active')->after('outstanding_balance');
                }
                if (!Schema::hasColumn('customers', 'first_purchase_date')) {
                    $table->date('first_purchase_date')->nullable()->after('status');
                }
                if (!Schema::hasColumn('customers', 'last_purchase_date')) {
                    $table->date('last_purchase_date')->nullable()->after('first_purchase_date');
                }
                if (!Schema::hasColumn('customers', 'total_orders')) {
                    $table->integer('total_orders')->default(0)->after('last_purchase_date');
                }
                if (!Schema::hasColumn('customers', 'average_order_value')) {
                    $table->decimal('average_order_value', 15, 2)->default(0)->after('total_orders');
                }
                if (!Schema::hasColumn('customers', 'primary_contact_name')) {
                    $table->string('primary_contact_name')->nullable()->after('average_order_value');
                }
                if (!Schema::hasColumn('customers', 'primary_contact_email')) {
                    $table->string('primary_contact_email')->nullable()->after('primary_contact_name');
                }
                if (!Schema::hasColumn('customers', 'primary_contact_phone')) {
                    $table->string('primary_contact_phone')->nullable()->after('primary_contact_email');
                }
                if (!Schema::hasColumn('customers', 'notes')) {
                    $table->text('notes')->nullable()->after('primary_contact_phone');
                }
                if (!Schema::hasColumn('customers', 'tags')) {
                    $table->json('tags')->nullable()->after('notes');
                }
                if (!Schema::hasColumn('customers', 'custom_fields')) {
                    $table->json('custom_fields')->nullable()->after('tags');
                }
                if (!Schema::hasColumn('customers', 'deleted_at')) {
                    $table->softDeletes();
                }
            });

            // Update existing payment_terms to enum if needed
            // Note: SQLite doesn't support altering enum, so we'll skip this for now
            // The application layer will handle the enum validation

            // Add indexes
            if (!Schema::hasColumn('customers', 'customer_code')) {
                Schema::table('customers', function (Blueprint $table) {
                    $table->index('customer_code');
                });
            }
        } else {
            // Create new table if it doesn't exist
            Schema::create('customers', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('organization_id');
                $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
                $table->unsignedBigInteger('customer_persona_id')->nullable();
                $table->foreign('customer_persona_id')->references('id')->on('customer_personas')->onDelete('set null');
                $table->string('customer_code')->unique();
                $table->enum('type', ['individual', 'business'])->default('business');
                
                // Basic Information
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('website')->nullable();
                $table->string('tax_id')->nullable();
                
                // Address
                $table->text('billing_address')->nullable();
                $table->text('shipping_address')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('country')->nullable();
                $table->string('postal_code')->nullable();
                
                // Financial
                $table->decimal('credit_limit', 15, 2)->default(0);
                $table->enum('payment_terms', ['immediate', 'net_15', 'net_30', 'net_60', 'net_90', 'custom'])->default('net_30');
                $table->integer('custom_payment_days')->nullable();
                $table->string('currency', 3)->default('USD');
                $table->decimal('lifetime_value', 15, 2)->default(0);
                $table->decimal('outstanding_balance', 15, 2)->default(0);
                
                // Relationship
                $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
                $table->date('first_purchase_date')->nullable();
                $table->date('last_purchase_date')->nullable();
                $table->integer('total_orders')->default(0);
                $table->decimal('average_order_value', 15, 2)->default(0);
                
                // Contact Person
                $table->string('primary_contact_name')->nullable();
                $table->string('primary_contact_email')->nullable();
                $table->string('primary_contact_phone')->nullable();
                
                // Notes
                $table->text('notes')->nullable();
                $table->json('tags')->nullable();
                $table->json('custom_fields')->nullable();
                
                $table->timestamps();
                $table->softDeletes();

                $table->index(['organization_id', 'status']);
                $table->index(['customer_code']);
                $table->index(['email']);
            });
        }
    }

    public function down(): void
    {
        // Don't drop the table, just remove the new columns if needed
        // This is safer for existing data
    }
};
