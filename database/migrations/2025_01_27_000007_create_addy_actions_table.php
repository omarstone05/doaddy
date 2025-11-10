<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addy_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chat_message_id')->nullable()->constrained('addy_chat_messages')->nullOnDelete();
            
            // Action details
            $table->string('action_type'); // send_invoice_reminders, create_transaction, etc.
            $table->string('category'); // money, sales, people, inventory
            $table->string('status')->default('pending'); // pending, confirmed, executed, failed, cancelled
            
            // Action data
            $table->json('parameters')->nullable(); // What the action needs
            $table->json('preview_data')->nullable(); // What will happen
            $table->json('result')->nullable(); // What happened
            
            // Execution tracking
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->text('error_message')->nullable();
            
            // Learning
            $table->boolean('was_successful')->nullable();
            $table->integer('user_rating')->nullable(); // 1-5 stars
            
            $table->timestamps();
            
            $table->index(['organization_id', 'status']);
            $table->index(['action_type', 'created_at']);
        });

        // Action learning patterns
        Schema::create('addy_action_patterns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            $table->string('action_type');
            $table->integer('times_suggested')->default(0);
            $table->integer('times_confirmed')->default(0);
            $table->integer('times_rejected')->default(0);
            $table->integer('times_successful')->default(0);
            $table->decimal('avg_rating', 3, 2)->nullable();
            
            // Context learning
            $table->json('successful_contexts')->nullable(); // When it worked
            $table->json('failed_contexts')->nullable(); // When it didn't
            
            $table->timestamps();
            
            $table->unique(['organization_id', 'user_id', 'action_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addy_action_patterns');
        Schema::dropIfExists('addy_actions');
    }
};

