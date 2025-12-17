<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds nullable foreign keys to link print jobs with quotations and invoices.
     * All foreign keys are nullable to ensure no breaking dependencies when
     * the PrintShop module is disabled.
     */
    public function up(): void
    {
        // Only proceed if print_jobs table exists (module is enabled)
        if (!Schema::hasTable('print_jobs')) {
            return;
        }

        // Add quotation_id and invoice_id to print_jobs table
        // Note: quotations.id is bigint, invoices.id is UUID
        if (Schema::hasColumn('print_jobs', 'quotation_id') === false) {
            Schema::table('print_jobs', function (Blueprint $table) {
                $table->unsignedBigInteger('quotation_id')->nullable()->after('customer_id');
                $table->foreign('quotation_id')->references('id')->on('quotations')->nullOnDelete();
                $table->index('quotation_id');
            });
        }

        if (Schema::hasColumn('print_jobs', 'invoice_id') === false) {
            Schema::table('print_jobs', function (Blueprint $table) {
                $table->foreignUuid('invoice_id')->nullable()->after('quotation_id');
                $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
                $table->index('invoice_id');
            });
        }

        // Add print_job_id to quotations table (nullable)
        // Note: print_jobs.id is bigint unsigned
        if (Schema::hasColumn('quotations', 'print_job_id') === false) {
            Schema::table('quotations', function (Blueprint $table) {
                $table->unsignedBigInteger('print_job_id')->nullable()->after('customer_id');
                $table->foreign('print_job_id')->references('id')->on('print_jobs')->nullOnDelete();
                $table->index('print_job_id');
            });
        }

        // Add print_job_id to invoices table (nullable)
        // Note: print_jobs.id is bigint unsigned
        if (Schema::hasColumn('invoices', 'print_job_id') === false) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('print_job_id')->nullable()->after('customer_id');
                $table->foreign('print_job_id')->references('id')->on('print_jobs')->nullOnDelete();
                $table->index('print_job_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safely remove columns if they exist
        if (Schema::hasColumn('invoices', 'print_job_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropForeign(['print_job_id']);
                $table->dropIndex(['print_job_id']);
                $table->dropColumn('print_job_id');
            });
        }

        if (Schema::hasColumn('quotations', 'print_job_id')) {
            Schema::table('quotations', function (Blueprint $table) {
                $table->dropForeign(['print_job_id']);
                $table->dropIndex(['print_job_id']);
                $table->dropColumn('print_job_id');
            });
        }

        if (Schema::hasTable('print_jobs')) {
            if (Schema::hasColumn('print_jobs', 'invoice_id')) {
                Schema::table('print_jobs', function (Blueprint $table) {
                    $table->dropForeign(['invoice_id']);
                    $table->dropIndex(['invoice_id']);
                    $table->dropColumn('invoice_id');
                });
            }

            if (Schema::hasColumn('print_jobs', 'quotation_id')) {
                Schema::table('print_jobs', function (Blueprint $table) {
                    $table->dropForeign(['quotation_id']);
                    $table->dropIndex(['quotation_id']);
                    $table->dropColumn('quotation_id');
                });
            }
        }
    }
};

