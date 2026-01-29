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
        Schema::create('digitax_credentials', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to organizations (UUID)
            $table->foreignUuid('organization_id')
                ->constrained('organizations')
                ->onDelete('cascade');
            
            // API Credentials (from Digitax business settings)
            $table->string('api_key')->encrypted();           // Serial Number (e.g., NAMI26012180421379KB7DAE)
            $table->string('api_secret')->encrypted();        // TPIN
            
            // Digitax API Key (unique per business, required for API authentication)
            $table->string('digitax_api_key')->encrypted();   // API Key: api_key_KC4gxhqWqcYlgdpnJdVBoyE34fjAChOn
            
            // Configuration
            $table->enum('environment', ['sandbox', 'production'])->default('sandbox');
            $table->boolean('is_active')->default(false);
            
            // Test results
            $table->timestamp('last_tested_at')->nullable();
            $table->json('test_result')->nullable();
            $table->text('test_error')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index(['organization_id', 'is_active']);
            $table->unique(['organization_id', 'environment']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digitax_credentials');
    }
};
