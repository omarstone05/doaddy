<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Vendors / Subcontractors
        Schema::create('consulting_vendors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->uuid('organization_id');
            
            // Vendor details
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            
            // Service details
            $table->string('service_type')->nullable(); // contractor, supplier, consultant, etc.
            $table->text('services_provided')->nullable();
            
            // Financial
            $table->json('quotes')->nullable(); // Array of quote documents/amounts
            $table->decimal('total_contracted', 15, 2)->default(0);
            $table->decimal('total_paid', 15, 2)->default(0);
            
            // Performance
            $table->integer('rating')->nullable(); // 1-5 stars
            $table->text('performance_notes')->nullable();
            
            // Status
            $table->string('status')->default('active'); // active, inactive, completed
            
            // Contacts
            $table->json('contacts')->nullable();
            
            // Files
            $table->json('documents')->nullable(); // Contracts, insurance, etc.
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['project_id', 'status']);
            $table->index('organization_id');
            
            $table->foreign('project_id')
                  ->references('id')
                  ->on('consulting_projects')
                  ->onDelete('cascade');
        });

        // Files & Documents
        Schema::create('consulting_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->uuid('folder_id')->nullable();
            
            // File details
            $table->string('name');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('file_type'); // mime type
            $table->integer('file_size'); // bytes
            $table->string('category')->nullable(); // contracts, designs, reports, photos, etc.
            
            // Upload info
            $table->uuid('uploaded_by');
            $table->timestamp('uploaded_at');
            
            // Versioning
            $table->integer('version')->default(1);
            $table->uuid('parent_file_id')->nullable(); // For versions
            
            // Permissions
            $table->boolean('visible_to_client')->default(false);
            $table->json('access_rules')->nullable();
            
            // Metadata
            $table->json('tags')->nullable();
            $table->text('description')->nullable();
            
            // Relations
            $table->string('related_type')->nullable(); // task, deliverable, expense, etc.
            $table->uuid('related_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['project_id', 'category']);
            $table->index('folder_id');
            $table->index('uploaded_by');
            $table->index(['related_type', 'related_id']);
            
            $table->foreign('project_id')
                  ->references('id')
                  ->on('consulting_projects')
                  ->onDelete('cascade');
        });

        // File Folders
        Schema::create('consulting_folders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->uuid('parent_folder_id')->nullable();
            
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            
            // Permissions
            $table->boolean('visible_to_client')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['project_id', 'parent_folder_id']);
            
            $table->foreign('project_id')
                  ->references('id')
                  ->on('consulting_projects')
                  ->onDelete('cascade');
        });

        // Communication / Comments
        Schema::create('consulting_communications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            
            // Message details
            $table->text('message');
            $table->string('type')->default('comment'); // comment, note, voice_note
            $table->string('channel')->default('internal'); // internal, client, mixed
            
            // Author
            $table->uuid('user_id');
            $table->string('user_name')->nullable(); // Cached
            
            // Context
            $table->string('related_type')->nullable(); // task, deliverable, expense, etc.
            $table->uuid('related_id')->nullable();
            
            // Thread
            $table->uuid('parent_id')->nullable(); // For replies
            
            // Attachments
            $table->json('attachments')->nullable();
            
            // Voice note (for construction/field use)
            $table->string('voice_file')->nullable();
            $table->integer('voice_duration')->nullable(); // seconds
            
            // Visibility
            $table->boolean('visible_to_client')->default(false);
            $table->boolean('is_pinned')->default(false);
            
            // Status
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['project_id', 'channel']);
            $table->index(['related_type', 'related_id']);
            $table->index('parent_id');
            $table->index('user_id');
            
            $table->foreign('project_id')
                  ->references('id')
                  ->on('consulting_projects')
                  ->onDelete('cascade');
        });

        // Activity Log
        Schema::create('consulting_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            
            // Activity details
            $table->string('action'); // created, updated, deleted, completed, approved, etc.
            $table->string('entity_type'); // task, deliverable, expense, etc.
            $table->uuid('entity_id');
            
            // User
            $table->uuid('user_id')->nullable();
            $table->string('user_name')->nullable();
            
            // Changes
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('description')->nullable();
            
            // Metadata
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            
            $table->timestamp('created_at');
            
            // Indexes
            $table->index(['project_id', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('user_id');
            
            $table->foreign('project_id')
                  ->references('id')
                  ->on('consulting_projects')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consulting_activities');
        Schema::dropIfExists('consulting_communications');
        Schema::dropIfExists('consulting_folders');
        Schema::dropIfExists('consulting_files');
        Schema::dropIfExists('consulting_vendors');
    }
};

