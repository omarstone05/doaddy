<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds DigiTax Smart Invoice tracking fields to invoices table.
     * These fields track the submission and completion status of invoices
     * with the ZRA Smart Invoice system via DigiTax.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // DigiTax sale identifier (returned after submission)
            $table->string('digitax_sale_id')->nullable()->after('terms');
            
            // Queue status tracking
            $table->enum('digitax_queue_status', [
                'pending',      // Not yet submitted
                'queued',       // Submitted, waiting in DigiTax queue
                'processing',   // DigiTax processing with ZRA
                'complete',     // Successfully processed, receipt_url available
                'failed'        // Failed after retries
            ])->nullable()->after('digitax_sale_id');
            
            // Receipt artifacts from ZRA (populated when queue_status = complete)
            $table->string('digitax_receipt_url')->nullable()->after('digitax_queue_status');
            $table->string('digitax_receipt_number')->nullable()->after('digitax_receipt_url');
            $table->string('digitax_serial_number')->nullable()->after('digitax_receipt_number');
            $table->text('digitax_receipt_signature')->nullable()->after('digitax_serial_number');
            
            // Full response data for debugging and audit
            $table->json('digitax_response')->nullable()->after('digitax_receipt_signature');
            $table->json('digitax_error')->nullable()->after('digitax_response');
            
            // Timestamps for tracking
            $table->timestamp('digitax_submitted_at')->nullable()->after('digitax_error');
            $table->timestamp('digitax_completed_at')->nullable()->after('digitax_submitted_at');
            
            // Retry tracking
            $table->unsignedTinyInteger('digitax_retry_count')->default(0)->after('digitax_completed_at');
            
            // Index for querying pending/processing invoices
            $table->index('digitax_queue_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['digitax_queue_status']);
            
            $table->dropColumn([
                'digitax_sale_id',
                'digitax_queue_status',
                'digitax_receipt_url',
                'digitax_receipt_number',
                'digitax_serial_number',
                'digitax_receipt_signature',
                'digitax_response',
                'digitax_error',
                'digitax_submitted_at',
                'digitax_completed_at',
                'digitax_retry_count',
            ]);
        });
    }
};
