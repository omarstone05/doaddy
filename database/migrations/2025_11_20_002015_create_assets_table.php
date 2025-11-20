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
        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            
            // Basic Information
            $table->string('name');
            $table->string('asset_number')->nullable()->unique();
            $table->string('asset_tag')->nullable();
            $table->string('category')->nullable(); // Equipment, Furniture, Vehicle, IT, etc.
            $table->text('description')->nullable();
            
            // Purchase Information
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->decimal('current_value', 12, 2)->nullable();
            $table->string('supplier')->nullable();
            $table->string('purchase_order_number')->nullable();
            
            // Asset Details
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('location')->nullable(); // Physical location
            $table->uuid('assigned_to_user_id')->nullable(); // Assigned to user
            $table->uuid('assigned_to_department_id')->nullable(); // Assigned to department
            
            // Status and Condition
            $table->enum('status', ['active', 'inactive', 'maintenance', 'retired', 'disposed', 'lost'])->default('active');
            $table->enum('condition', ['excellent', 'good', 'fair', 'poor', 'needs_repair'])->default('good');
            
            // Warranty and Maintenance
            $table->date('warranty_expiry')->nullable();
            $table->date('last_maintenance_date')->nullable();
            $table->date('next_maintenance_date')->nullable();
            $table->text('maintenance_notes')->nullable();
            
            // Depreciation (optional)
            $table->enum('depreciation_method', ['straight_line', 'declining_balance', 'none'])->default('none');
            $table->integer('useful_life_years')->nullable();
            $table->decimal('salvage_value', 12, 2)->nullable();
            $table->decimal('accumulated_depreciation', 12, 2)->default(0);
            $table->date('last_depreciation_date')->nullable();
            
            // Additional Information
            $table->json('metadata')->nullable(); // For custom fields
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable(); // File paths/images
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'category']);
            $table->index('assigned_to_user_id');
            $table->index('assigned_to_department_id');
            
            // Foreign Keys
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
                  
            $table->foreign('assigned_to_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
                  
            $table->foreign('assigned_to_department_id')
                  ->references('id')
                  ->on('departments')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
