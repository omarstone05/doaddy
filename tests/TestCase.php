<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Organization;
use App\Models\User;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    protected Organization $testOrganization;
    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test organization and user for each test
        $this->testOrganization = Organization::factory()->create([
            'name' => 'Test Org',
            'slug' => 'test-org',
        ]);

        $this->testUser = User::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'email' => 'test@example.com',
        ]);

        // Set up test AI API keys (use dummy keys for testing)
        \App\Models\PlatformSetting::set('ai_provider', 'openai', 'string');
        \App\Models\PlatformSetting::set('openai_api_key', 'test-api-key-12345', 'encrypted');
        \App\Models\PlatformSetting::set('openai_model', 'gpt-4o', 'string');
    }

    /**
     * Authenticate as test user
     */
    protected function authenticate(): self
    {
        $this->actingAs($this->testUser);
        return $this;
    }

    /**
     * Create a different organization for multi-tenant tests
     */
    protected function createOtherOrganization(): Organization
    {
        return Organization::factory()->create();
    }

    /**
     * Seed test data for comprehensive tests
     */
    protected function seedTestData(): void
    {
        $this->seed(\Database\Seeders\TestDataSeeder::class);
    }
}
