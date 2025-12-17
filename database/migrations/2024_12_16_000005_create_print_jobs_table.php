<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('job_number')->unique();
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('print_material_id')->constrained('print_materials')->restrictOnDelete();
            $table->foreignId('ink_configuration_id')->constrained('ink_configurations')->restrictOnDelete();
            $table->foreignId('pricing_rule_id')->nullable()->constrained('pricing_rules')->nullOnDelete();
            
            // Dimensions
            $table->decimal('width', 10, 2);
            $table->decimal('height', 10, 2);
            $table->integer('quantity')->default(1);
            
            // Cost Breakdown (stored for historical accuracy)
            $table->decimal('material_unit_cost', 15, 2);
            $table->decimal('ink_unit_cost', 15, 2);
            $table->decimal('off_cut_cost', 15, 2)->default(0);
            
            // Pricing
            $table->decimal('price_per_sqm', 15, 2);
            
            // Additional Costs
            $table->decimal('setup_cost', 15, 2)->default(0);
            $table->decimal('finishing_cost', 15, 2)->default(0);
            $table->decimal('delivery_cost', 15, 2)->default(0);
            $table->decimal('other_costs', 15, 2)->default(0);
            
            // Status
            $table->enum('status', ['draft', 'quoted', 'approved', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->timestamp('quoted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('organization_id');
            $table->index('customer_id');
            $table->index('job_number');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_jobs');
    }
};

