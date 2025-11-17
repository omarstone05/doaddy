<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create businesses table
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('business_type')->nullable(); // retail, consulting, agriculture, etc.
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('currency', 3)->default('ZMW');
            $table->string('timezone')->default('Africa/Lusaka');
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('subscription_ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Create roles table
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // owner, admin, manager, employee, accountant
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('permissions'); // Array of permission keys
            $table->integer('level')->default(0); // Higher = more power
            $table->timestamps();
        });

        // 3. Create business_user pivot table (many-to-many with role)
        Schema::create('business_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // User can only have one role per business
            $table->unique(['business_id', 'user_id']);
        });

        // 4. Add current business tracking to users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_business_id')->nullable()
                ->after('email_verified_at')
                ->constrained('businesses')
                ->nullOnDelete();
        });

        // 5. Add business_id to all tenant tables
        $tenantTables = [
            'transactions',
            'customers',
            'products',
            'invoices',
            'expenses',
            // Add all your business-specific tables here
        ];

        foreach ($tenantTables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('business_id')
                        ->after('id')
                        ->constrained()
                        ->onDelete('cascade');
                    
                    // Add index for faster queries
                    $table->index(['business_id', 'created_at']);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop in reverse order
        $tenantTables = [
            'transactions',
            'customers',
            'products',
            'invoices',
            'expenses',
        ];

        foreach ($tenantTables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['business_id']);
                    $table->dropColumn('business_id');
                });
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_business_id']);
            $table->dropColumn('current_business_id');
        });

        Schema::dropIfExists('business_user');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('businesses');
    }
};
