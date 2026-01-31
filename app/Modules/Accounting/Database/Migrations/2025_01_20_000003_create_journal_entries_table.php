<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('entry_number', 50)->unique();
            $table->date('entry_date');
            $table->text('description');
            $table->text('reference')->nullable(); // Reference number/document
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('draft');
            $table->enum('type', ['manual', 'system', 'recurring', 'adjusting', 'closing'])->default('manual');
            $table->uuid('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->uuid('reversed_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->uuid('reversing_entry_id')->nullable(); // Link to reversing entry
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('posted_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('reversed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('reversing_entry_id')->references('id')->on('journal_entries')->onDelete('set null');
            
            $table->index(['organization_id', 'entry_date', 'status']);
            $table->index(['entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};

