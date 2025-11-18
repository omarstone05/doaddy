<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->uuid('user_id');
            $table->uuid('organization_id');
            $table->enum('role', ['manager', 'member', 'viewer', 'contributor'])->default('member');
            $table->json('permissions')->nullable();
            $table->date('joined_at');
            $table->timestamps();
            
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->unique(['project_id', 'user_id']);
            $table->index(['project_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_members');
    }
};

