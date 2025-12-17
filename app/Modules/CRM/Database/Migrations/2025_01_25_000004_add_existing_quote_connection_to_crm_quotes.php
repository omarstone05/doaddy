<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_quotes')) {
            Schema::table('crm_quotes', function (Blueprint $table) {
                // Link to existing quotes table
                $table->uuid('existing_quote_id')->nullable()->after('invoice_id');
                $table->index('existing_quote_id');
                
                // Add foreign key if quotes table exists
                if (Schema::hasTable('quotes')) {
                    $table->foreign('existing_quote_id')
                          ->references('id')
                          ->on('quotes')
                          ->onDelete('set null');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('crm_quotes')) {
            Schema::table('crm_quotes', function (Blueprint $table) {
                if (Schema::hasColumn('crm_quotes', 'existing_quote_id')) {
                    $table->dropForeign(['existing_quote_id']);
                    $table->dropColumn('existing_quote_id');
                }
            });
        }
    }
};

