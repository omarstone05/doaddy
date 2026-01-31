<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds performance indexes for common query patterns:
     * - User lookups by organization_id (multi-tenant queries)
     * - User lookups by penda_account_id (SSO authentication)
     * - User status checks (is_active filtering)
     * - Organization status filtering
     * - Organization onboarding status filtering
     */
    public function up(): void
    {
        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            // Index for organization_id (if not already indexed via composite unique)
            if (!$this->indexExists('users', 'users_organization_id_index')) {
                $table->index('organization_id', 'users_organization_id_index');
            }
        });
        
        // Add is_active index if column exists
        if (Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                if (!$this->indexExists('users', 'users_is_active_index')) {
                    $table->index('is_active', 'users_is_active_index');
                }
            });
        }
        
        // Add email index if not already indexed
        Schema::table('users', function (Blueprint $table) {
            if (!$this->indexExists('users', 'users_email_index')) {
                $table->index('email', 'users_email_index');
            }
        });
        
        // Organizations table indexes
        if (Schema::hasColumn('organizations', 'status')) {
            Schema::table('organizations', function (Blueprint $table) {
                if (!$this->indexExists('organizations', 'organizations_status_index')) {
                    $table->index('status', 'organizations_status_index');
                }
            });
        }
        
        if (Schema::hasColumn('organizations', 'onboarding_status')) {
            Schema::table('organizations', function (Blueprint $table) {
                if (!$this->indexExists('organizations', 'organizations_onboarding_status_index')) {
                    $table->index('onboarding_status', 'organizations_onboarding_status_index');
                }
            });
        }
        
        if (Schema::hasColumn('organizations', 'trial_ends_at')) {
            Schema::table('organizations', function (Blueprint $table) {
                if (!$this->indexExists('organizations', 'organizations_trial_ends_at_index')) {
                    $table->index('trial_ends_at', 'organizations_trial_ends_at_index');
                }
            });
        }
        
        // Organization_user pivot table indexes (if not already indexed)
        if (Schema::hasTable('organization_user')) {
            if (Schema::hasColumn('organization_user', 'role')) {
                Schema::table('organization_user', function (Blueprint $table) {
                    if (!$this->indexExists('organization_user', 'organization_user_role_index')) {
                        $table->index('role', 'organization_user_role_index');
                    }
                });
            }
            
            if (Schema::hasColumn('organization_user', 'is_active')) {
                Schema::table('organization_user', function (Blueprint $table) {
                    if (!$this->indexExists('organization_user', 'organization_user_is_active_index')) {
                        $table->index('is_active', 'organization_user_is_active_index');
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_organization_id_index');
            $table->dropIndex('users_is_active_index');
            $table->dropIndex('users_email_index');
        });
        
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropIndex('organizations_status_index');
            $table->dropIndex('organizations_onboarding_status_index');
            $table->dropIndex('organizations_trial_ends_at_index');
        });
        
        if (Schema::hasTable('organization_user')) {
            Schema::table('organization_user', function (Blueprint $table) {
                $table->dropIndex('organization_user_role_index');
                $table->dropIndex('organization_user_is_active_index');
            });
        }
    }
    
    /**
     * Check if an index exists on a table (SQLite compatible).
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");
            foreach ($indexes as $index) {
                if ($index->name === $indexName) {
                    return true;
                }
            }
            return false;
        }
        
        // MySQL
        if ($driver === 'mysql') {
            $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            return count($indexes) > 0;
        }
        
        // PostgreSQL
        if ($driver === 'pgsql') {
            $indexes = DB::select("SELECT * FROM pg_indexes WHERE tablename = ? AND indexname = ?", [$table, $indexName]);
            return count($indexes) > 0;
        }
        
        // Default: assume index doesn't exist
        return false;
    }
};
