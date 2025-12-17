<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_approvals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('budget_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('workflow_id')->constrained('budget_approval_workflows')->cascadeOnDelete();
            $table->integer('stage_number')->default(1);
            $table->string('stage_name')->nullable();
            $table->foreignUuid('approver_id')->constrained('users');
            $table->enum('status', ['pending', 'waiting', 'approved', 'rejected', 'modified'])->default('pending');
            $table->json('modifications')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_approvals');
    }
};
