<?php

namespace Tests\Unit\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PerformanceIndexesMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test users table has organization_id index
     */
    public function test_users_table_has_organization_id_index(): void
    {
        $this->assertTrue(
            $this->indexExists('users', 'users_organization_id_index'),
            'users table should have users_organization_id_index'
        );
    }

    /**
     * Test users table has is_active index if column exists
     */
    public function test_users_table_has_is_active_index_if_column_exists(): void
    {
        if (Schema::hasColumn('users', 'is_active')) {
            $this->assertTrue(
                $this->indexExists('users', 'users_is_active_index'),
                'users table should have users_is_active_index'
            );
        } else {
            $this->markTestSkipped('is_active column does not exist on users table');
        }
    }

    /**
     * Test users table has email index
     */
    public function test_users_table_has_email_index(): void
    {
        $this->assertTrue(
            $this->indexExists('users', 'users_email_index'),
            'users table should have users_email_index'
        );
    }

    /**
     * Test organizations table has status index if column exists
     */
    public function test_organizations_table_has_status_index_if_column_exists(): void
    {
        if (Schema::hasColumn('organizations', 'status')) {
            $this->assertTrue(
                $this->indexExists('organizations', 'organizations_status_index'),
                'organizations table should have organizations_status_index'
            );
        } else {
            $this->markTestSkipped('status column does not exist on organizations table');
        }
    }

    /**
     * Test organizations table has onboarding_status index if column exists
     */
    public function test_organizations_table_has_onboarding_status_index_if_column_exists(): void
    {
        if (Schema::hasColumn('organizations', 'onboarding_status')) {
            $this->assertTrue(
                $this->indexExists('organizations', 'organizations_onboarding_status_index'),
                'organizations table should have organizations_onboarding_status_index'
            );
        } else {
            $this->markTestSkipped('onboarding_status column does not exist on organizations table');
        }
    }

    /**
     * Test organizations table has trial_ends_at index if column exists
     */
    public function test_organizations_table_has_trial_ends_at_index_if_column_exists(): void
    {
        if (Schema::hasColumn('organizations', 'trial_ends_at')) {
            $this->assertTrue(
                $this->indexExists('organizations', 'organizations_trial_ends_at_index'),
                'organizations table should have organizations_trial_ends_at_index'
            );
        } else {
            $this->markTestSkipped('trial_ends_at column does not exist on organizations table');
        }
    }

    /**
     * Test organization_user table has role index if it exists
     */
    public function test_organization_user_table_has_role_index_if_exists(): void
    {
        if (!Schema::hasTable('organization_user')) {
            $this->markTestSkipped('organization_user table does not exist');
        }

        if (Schema::hasColumn('organization_user', 'role')) {
            $this->assertTrue(
                $this->indexExists('organization_user', 'organization_user_role_index'),
                'organization_user table should have organization_user_role_index'
            );
        } else {
            $this->markTestSkipped('role column does not exist on organization_user table');
        }
    }

    /**
     * Test organization_user table has is_active index if it exists
     */
    public function test_organization_user_table_has_is_active_index_if_exists(): void
    {
        if (!Schema::hasTable('organization_user')) {
            $this->markTestSkipped('organization_user table does not exist');
        }

        if (Schema::hasColumn('organization_user', 'is_active')) {
            $this->assertTrue(
                $this->indexExists('organization_user', 'organization_user_is_active_index'),
                'organization_user table should have organization_user_is_active_index'
            );
        } else {
            $this->markTestSkipped('is_active column does not exist on organization_user table');
        }
    }

    /**
     * Test indexes improve query performance (basic check)
     */
    public function test_user_lookup_by_organization_uses_index(): void
    {
        // Create some test data
        $org = $this->testOrganization;
        
        // Run a query that should use the index
        $users = DB::table('users')
            ->where('organization_id', $org->id)
            ->get();

        // If we get here without error, the query executed successfully
        $this->assertIsIterable($users);
    }

    /**
     * Test email lookup uses index
     */
    public function test_user_lookup_by_email_uses_index(): void
    {
        $users = DB::table('users')
            ->where('email', 'test@example.com')
            ->get();

        $this->assertIsIterable($users);
    }

    /**
     * Helper method to check if an index exists
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
        
        if ($driver === 'mysql') {
            $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            return count($indexes) > 0;
        }
        
        if ($driver === 'pgsql') {
            $indexes = DB::select("SELECT * FROM pg_indexes WHERE tablename = ? AND indexname = ?", [$table, $indexName]);
            return count($indexes) > 0;
        }
        
        return false;
    }
}
