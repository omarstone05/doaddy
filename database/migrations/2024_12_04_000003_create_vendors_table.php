<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendors')) {
            // Table exists, add missing columns
            Schema::table('vendors', function (Blueprint $table) {
                $columnsToAdd = [
                    'vendor_code' => 'string',
                    'type' => 'enum',
                    'website' => 'string',
                    'city' => 'string',
                    'state' => 'string',
                    'country' => 'string',
                    'postal_code' => 'string',
                    'custom_payment_days' => 'integer',
                    'currency' => 'string',
                    'total_spent' => 'decimal',
                    'outstanding_balance' => 'decimal',
                    'bank_name' => 'string',
                    'bank_account_number' => 'string',
                    'bank_routing_number' => 'string',
                    'payment_method' => 'string',
                    'rating' => 'enum',
                    'first_transaction_date' => 'date',
                    'last_transaction_date' => 'date',
                    'total_transactions' => 'integer',
                    'primary_contact_name' => 'string',
                    'primary_contact_email' => 'string',
                    'primary_contact_phone' => 'string',
                    'tags' => 'json',
                    'custom_fields' => 'json',
                ];

                foreach ($columnsToAdd as $column => $type) {
                    if (!Schema::hasColumn('vendors', $column)) {
                        if ($type === 'string') {
                            $table->string($column)->nullable();
                        } elseif ($type === 'integer') {
                            $table->integer($column)->default(0);
                        } elseif ($type === 'decimal') {
                            $table->decimal($column, 15, 2)->default(0);
                        } elseif ($type === 'date') {
                            $table->date($column)->nullable();
                        } elseif ($type === 'json') {
                            $table->json($column)->nullable();
                        } elseif ($type === 'enum') {
                            if ($column === 'type') {
                                $table->enum('type', ['individual', 'business'])->default('business')->nullable();
                            } elseif ($column === 'rating') {
                                $table->enum('rating', ['excellent', 'good', 'fair', 'poor'])->nullable();
                            }
                        }
                    }
                }

                if (!Schema::hasColumn('vendors', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
            return;
        }

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->uuid('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->string('vendor_code')->unique();
            $table->enum('type', ['individual', 'business'])->default('business');
            
            // Basic Information
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('tax_id')->nullable();
            
            // Address
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            
            // Financial
            $table->enum('payment_terms', ['immediate', 'net_15', 'net_30', 'net_60', 'net_90', 'custom'])->default('net_30');
            $table->integer('custom_payment_days')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->decimal('total_spent', 15, 2)->default(0);
            $table->decimal('outstanding_balance', 15, 2)->default(0);
            
            // Banking
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_routing_number')->nullable();
            $table->string('payment_method')->nullable(); // bank_transfer, check, cash, mobile_money
            
            // Relationship
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
            $table->enum('rating', ['excellent', 'good', 'fair', 'poor'])->nullable();
            $table->date('first_transaction_date')->nullable();
            $table->date('last_transaction_date')->nullable();
            $table->integer('total_transactions')->default(0);
            
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
            $table->index(['vendor_code']);
            $table->index(['email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
