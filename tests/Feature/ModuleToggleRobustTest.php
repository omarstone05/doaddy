<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationRole;
use App\Models\User;
use App\Support\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comprehensive tests for module toggle functionality.
 * 
 * These tests ensure that:
 * 1. Module toggling uses isEnabled() to check actual organization state
 * 2. The toggle correctly enables/disables modules
 * 3. The system is robust against adding new modules
 * 4. Organization isolation works correctly
 * 5. Edge cases are handled properly
 */
class ModuleToggleRobustTest extends TestCase
{
    use RefreshDatabase;

    protected ModuleManager $moduleManager;
    protected string $testModuleName;
    protected string $testModuleAlias;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->moduleManager = new ModuleManager();
        
        // Get a real module to test with
        $modules = $this->moduleManager->all();
        if (!empty($modules)) {
            // Use HR module as it's commonly available
            $this->testModuleName = array_key_exists('HR', $modules) ? 'HR' : array_key_first($modules);
            $module = $modules[$this->testModuleName];
            $this->testModuleAlias = $module['config']['alias'] ?? strtolower($this->testModuleName);
        }
        
        // Seed organization roles for permission checks
        $this->seedOrganizationRoles();
    }

    protected function seedOrganizationRoles(): void
    {
        if (!OrganizationRole::where('slug', 'owner')->exists()) {
            OrganizationRole::create([
                'name' => 'Owner',
                'slug' => 'owner',
                'level' => 100,
                'permissions' => ['*'],
                'is_system' => true,
            ]);
        }
        
        if (!OrganizationRole::where('slug', 'admin')->exists()) {
            OrganizationRole::create([
                'name' => 'Admin',
                'slug' => 'admin',
                'level' => 90,
                'permissions' => ['modules.manage', 'users.manage'],
                'is_system' => true,
            ]);
        }
        
        if (!OrganizationRole::where('slug', 'member')->exists()) {
            OrganizationRole::create([
                'name' => 'Member',
                'slug' => 'member',
                'level' => 10,
                'permissions' => [],
                'is_system' => true,
            ]);
        }
    }

    // ==========================================
    // CORE TOGGLE FUNCTIONALITY TESTS
    // ==========================================

    /** @test */
    public function toggle_uses_is_enabled_not_module_data_enabled(): void
    {
        $this->authenticate();
        
        // Set the organization to have the module enabled
        $this->testOrganization->enabled_modules = [$this->testModuleAlias];
        $this->testOrganization->save();
        
        // Verify isEnabled returns true
        $moduleManager = new ModuleManager();
        $this->assertTrue($moduleManager->isEnabled($this->testModuleName));
        
        // Now toggle it (should disable since it's currently enabled)
        $response = $this->postJson("/modules/{$this->testModuleName}/toggle");
        
        $response->assertSuccessful();
        $response->assertJson(['success' => true]);
        
        // Verify the module is now disabled
        $this->testOrganization->refresh();
        $this->assertNotContains($this->testModuleAlias, $this->testOrganization->enabled_modules ?? []);
    }

    /** @test */
    public function toggle_can_enable_disabled_module(): void
    {
        $this->authenticate();
        
        // Start with module disabled
        $this->testOrganization->enabled_modules = [];
        $this->testOrganization->save();
        
        // Verify it's disabled
        $moduleManager = new ModuleManager();
        $this->assertFalse($moduleManager->isEnabled($this->testModuleName));
        
        // Toggle it (should enable)
        $response = $this->postJson("/modules/{$this->testModuleName}/toggle");
        
        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
            'message' => 'Module enabled successfully',
            'module' => [
                'name' => $this->testModuleName,
                'enabled' => true,
            ],
        ]);
        
        // Verify it's now enabled
        $this->testOrganization->refresh();
        $this->assertContains($this->testModuleAlias, $this->testOrganization->enabled_modules);
    }

    /** @test */
    public function toggle_can_disable_enabled_module(): void
    {
        $this->authenticate();
        
        // Start with module enabled
        $this->testOrganization->enabled_modules = [$this->testModuleAlias];
        $this->testOrganization->save();
        
        // Verify it's enabled
        $moduleManager = new ModuleManager();
        $this->assertTrue($moduleManager->isEnabled($this->testModuleName));
        
        // Toggle it (should disable)
        $response = $this->postJson("/modules/{$this->testModuleName}/toggle");
        
        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
            'message' => 'Module disabled successfully',
            'module' => [
                'name' => $this->testModuleName,
                'enabled' => false,
            ],
        ]);
        
        // Verify it's now disabled
        $this->testOrganization->refresh();
        $this->assertNotContains($this->testModuleAlias, $this->testOrganization->enabled_modules ?? []);
    }

    /** @test */
    public function double_toggle_returns_to_original_state(): void
    {
        $this->authenticate();
        
        // Start with module enabled
        $this->testOrganization->enabled_modules = [$this->testModuleAlias];
        $this->testOrganization->save();
        
        // First toggle (disable)
        $this->postJson("/modules/{$this->testModuleName}/toggle")->assertSuccessful();
        
        $this->testOrganization->refresh();
        $this->assertNotContains($this->testModuleAlias, $this->testOrganization->enabled_modules ?? []);
        
        // Second toggle (enable)
        $this->postJson("/modules/{$this->testModuleName}/toggle")->assertSuccessful();
        
        $this->testOrganization->refresh();
        $this->assertContains($this->testModuleAlias, $this->testOrganization->enabled_modules);
    }

    // ==========================================
    // ORGANIZATION ISOLATION TESTS
    // ==========================================

    /** @test */
    public function toggle_only_affects_current_organization(): void
    {
        $this->authenticate();
        
        // Create another organization with the module enabled
        $otherOrg = Organization::factory()->create([
            'enabled_modules' => [$this->testModuleAlias],
        ]);
        
        // Current org has module disabled
        $this->testOrganization->enabled_modules = [];
        $this->testOrganization->save();
        
        // Toggle module for current org
        $this->postJson("/modules/{$this->testModuleName}/toggle")->assertSuccessful();
        
        // Other org should be unaffected
        $otherOrg->refresh();
        $this->assertContains($this->testModuleAlias, $otherOrg->enabled_modules);
        
        // Current org should now have it enabled
        $this->testOrganization->refresh();
        $this->assertContains($this->testModuleAlias, $this->testOrganization->enabled_modules);
    }

    /** @test */
    public function is_enabled_checks_correct_organization(): void
    {
        $this->authenticate();
        
        // Set up two orgs with different module states
        $this->testOrganization->enabled_modules = [$this->testModuleAlias];
        $this->testOrganization->save();
        
        $otherOrg = Organization::factory()->create([
            'enabled_modules' => [],
        ]);
        
        // For current org, module should be enabled
        $moduleManager = new ModuleManager();
        $this->assertTrue($moduleManager->isEnabled($this->testModuleName));
    }

    // ==========================================
    // API RESPONSE FORMAT TESTS
    // ==========================================

    /** @test */
    public function toggle_returns_json_with_correct_structure(): void
    {
        $this->authenticate();
        
        $this->testOrganization->enabled_modules = [];
        $this->testOrganization->save();
        
        $response = $this->postJson("/modules/{$this->testModuleName}/toggle");
        
        $response->assertSuccessful();
        $response->assertJsonStructure([
            'success',
            'message',
            'module' => [
                'name',
                'enabled',
            ],
        ]);
    }

    /** @test */
    public function get_all_modules_reflects_current_state(): void
    {
        $this->authenticate();
        
        // Set specific modules as enabled
        $this->testOrganization->enabled_modules = [$this->testModuleAlias];
        $this->testOrganization->save();
        
        $response = $this->getJson('/api/modules/all');
        
        $response->assertSuccessful();
        
        $modules = $response->json('modules');
        $testModule = collect($modules)->firstWhere('name', $this->testModuleName);
        
        if ($testModule) {
            $this->assertTrue($testModule['enabled']);
        }
    }

    /** @test */
    public function get_all_modules_updates_after_toggle(): void
    {
        $this->authenticate();
        
        // Start with module disabled
        $this->testOrganization->enabled_modules = [];
        $this->testOrganization->save();
        
        // Check initial state
        $response1 = $this->getJson('/api/modules/all');
        $modules1 = $response1->json('modules');
        $testModule1 = collect($modules1)->firstWhere('name', $this->testModuleName);
        
        if ($testModule1) {
            $this->assertFalse($testModule1['enabled']);
        }
        
        // Toggle
        $this->postJson("/modules/{$this->testModuleName}/toggle")->assertSuccessful();
        
        // Check updated state
        $response2 = $this->getJson('/api/modules/all');
        $modules2 = $response2->json('modules');
        $testModule2 = collect($modules2)->firstWhere('name', $this->testModuleName);
        
        if ($testModule2) {
            $this->assertTrue($testModule2['enabled']);
        }
    }

    // ==========================================
    // AUTHORIZATION TESTS
    // ==========================================

    /** @test */
    public function toggle_requires_authentication(): void
    {
        // Don't authenticate
        $response = $this->postJson("/modules/{$this->testModuleName}/toggle");
        
        $this->assertTrue(
            $response->status() === 401 || 
            $response->status() === 403 ||
            $response->isRedirect()
        );
    }

    /** @test */
    public function owner_can_toggle_modules(): void
    {
        $this->authenticate();
        
        // User is already owner from setUp
        $this->testOrganization->enabled_modules = [];
        $this->testOrganization->save();
        
        $response = $this->postJson("/modules/{$this->testModuleName}/toggle");
        
        $response->assertSuccessful();
    }

    /** @test */
    public function admin_can_toggle_modules(): void
    {
        // Create admin user
        $adminRole = OrganizationRole::where('slug', 'admin')->first();
        
        $adminUser = User::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);
        
        $adminUser->organizations()->attach($this->testOrganization->id, [
            'role' => 'admin',
            'role_id' => $adminRole?->id,
            'is_active' => true,
            'joined_at' => now(),
        ]);
        
        $this->actingAs($adminUser);
        
        $this->testOrganization->enabled_modules = [];
        $this->testOrganization->save();
        
        $response = $this->postJson("/modules/{$this->testModuleName}/toggle");
        
        $response->assertSuccessful();
    }

    /** @test */
    public function member_cannot_toggle_modules(): void
    {
        // Create member user
        $memberRole = OrganizationRole::where('slug', 'member')->first();
        
        $memberUser = User::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);
        
        $memberUser->organizations()->attach($this->testOrganization->id, [
            'role' => 'member',
            'role_id' => $memberRole?->id,
            'is_active' => true,
            'joined_at' => now(),
        ]);
        
        $this->actingAs($memberUser);
        
        $this->testOrganization->enabled_modules = [];
        $this->testOrganization->save();
        
        $response = $this->postJson("/modules/{$this->testModuleName}/toggle");
        
        $response->assertStatus(403);
    }

    // ==========================================
    // EDGE CASES AND ROBUSTNESS TESTS
    // ==========================================

    /** @test */
    public function toggle_nonexistent_module_returns_404(): void
    {
        $this->authenticate();
        
        $response = $this->postJson('/modules/NonExistentModule/toggle');
        
        $response->assertStatus(404);
    }

    /** @test */
    public function toggle_handles_null_enabled_modules(): void
    {
        $this->authenticate();
        
        // Set enabled_modules to null (edge case for new organizations)
        $this->testOrganization->enabled_modules = null;
        $this->testOrganization->save();
        
        $response = $this->postJson("/modules/{$this->testModuleName}/toggle");
        
        $response->assertSuccessful();
        
        // Should now have the module enabled
        $this->testOrganization->refresh();
        $this->assertContains($this->testModuleAlias, $this->testOrganization->enabled_modules);
    }

    /** @test */
    public function toggle_handles_empty_enabled_modules_array(): void
    {
        $this->authenticate();
        
        $this->testOrganization->enabled_modules = [];
        $this->testOrganization->save();
        
        $response = $this->postJson("/modules/{$this->testModuleName}/toggle");
        
        $response->assertSuccessful();
        
        $this->testOrganization->refresh();
        $this->assertContains($this->testModuleAlias, $this->testOrganization->enabled_modules);
    }

    /** @test */
    public function enabling_already_enabled_module_toggles_it_off(): void
    {
        $this->authenticate();
        
        // Module is already enabled
        $this->testOrganization->enabled_modules = [$this->testModuleAlias];
        $this->testOrganization->save();
        
        // Toggle should disable it (not just do nothing)
        $response = $this->postJson("/modules/{$this->testModuleName}/toggle");
        
        $response->assertSuccessful();
        $response->assertJson(['message' => 'Module disabled successfully']);
        
        $this->testOrganization->refresh();
        $this->assertNotContains($this->testModuleAlias, $this->testOrganization->enabled_modules ?? []);
    }

    /** @test */
    public function module_alias_and_name_both_work_for_enable_check(): void
    {
        $this->authenticate();
        
        // Enable using alias in the database
        $this->testOrganization->enabled_modules = [$this->testModuleAlias];
        $this->testOrganization->save();
        
        // isEnabled should work with the NAME (not just alias)
        $moduleManager = new ModuleManager();
        $this->assertTrue($moduleManager->isEnabled($this->testModuleName));
    }

    /** @test */
    public function multiple_modules_can_be_enabled_independently(): void
    {
        $this->authenticate();
        
        $modules = $this->moduleManager->all();
        if (count($modules) < 2) {
            $this->markTestSkipped('Need at least 2 modules for this test');
        }
        
        // Get two module names
        $moduleNames = array_keys($modules);
        $module1 = $moduleNames[0];
        $module2 = $moduleNames[1];
        $alias1 = $modules[$module1]['config']['alias'] ?? strtolower($module1);
        $alias2 = $modules[$module2]['config']['alias'] ?? strtolower($module2);
        
        // Start with both disabled
        $this->testOrganization->enabled_modules = [];
        $this->testOrganization->save();
        
        // Enable first module
        $this->postJson("/modules/{$module1}/toggle")->assertSuccessful();
        
        $this->testOrganization->refresh();
        $this->assertContains($alias1, $this->testOrganization->enabled_modules);
        $this->assertNotContains($alias2, $this->testOrganization->enabled_modules);
        
        // Enable second module
        $this->postJson("/modules/{$module2}/toggle")->assertSuccessful();
        
        $this->testOrganization->refresh();
        $this->assertContains($alias1, $this->testOrganization->enabled_modules);
        $this->assertContains($alias2, $this->testOrganization->enabled_modules);
    }

    // ==========================================
    // ORGANIZATION CONTEXT DETECTION TESTS
    // ==========================================

    /** @test */
    public function toggle_uses_user_organization_id_as_fallback(): void
    {
        $this->authenticate();
        
        // Clear any session-based org ID
        session()->forget('current_organization_id');
        
        // Ensure user has organization_id set (don't touch current_organization_id as it may not exist)
        $this->testUser->organization_id = $this->testOrganization->id;
        $this->testUser->save();
        
        $this->testOrganization->enabled_modules = [];
        $this->testOrganization->save();
        
        $response = $this->postJson("/modules/{$this->testModuleName}/toggle");
        
        $response->assertSuccessful();
        
        $this->testOrganization->refresh();
        $this->assertContains($this->testModuleAlias, $this->testOrganization->enabled_modules);
    }

    /** @test */
    public function toggle_uses_session_organization_id_when_available(): void
    {
        $this->authenticate();
        
        // Set session-based org ID
        session(['current_organization_id' => $this->testOrganization->id]);
        
        $this->testOrganization->enabled_modules = [];
        $this->testOrganization->save();
        
        $response = $this->postJson("/modules/{$this->testModuleName}/toggle");
        
        $response->assertSuccessful();
        
        $this->testOrganization->refresh();
        $this->assertContains($this->testModuleAlias, $this->testOrganization->enabled_modules);
    }

    // ==========================================
    // DATA INTEGRITY TESTS
    // ==========================================

    /** @test */
    public function toggle_does_not_duplicate_module_in_enabled_array(): void
    {
        $this->authenticate();
        
        // Start with module disabled
        $this->testOrganization->enabled_modules = [];
        $this->testOrganization->save();
        
        // Enable it twice
        $this->postJson("/modules/{$this->testModuleName}/toggle")->assertSuccessful();
        
        // Now disable and re-enable
        $this->postJson("/modules/{$this->testModuleName}/toggle")->assertSuccessful();
        $this->postJson("/modules/{$this->testModuleName}/toggle")->assertSuccessful();
        
        $this->testOrganization->refresh();
        
        // Count occurrences of the alias
        $count = count(array_filter(
            $this->testOrganization->enabled_modules,
            fn($m) => $m === $this->testModuleAlias
        ));
        
        $this->assertEquals(1, $count, 'Module should appear only once in enabled_modules');
    }

    /** @test */
    public function toggle_preserves_other_enabled_modules(): void
    {
        $this->authenticate();
        
        $modules = $this->moduleManager->all();
        if (count($modules) < 2) {
            $this->markTestSkipped('Need at least 2 modules for this test');
        }
        
        // Get two module aliases
        $moduleNames = array_keys($modules);
        $module1 = $moduleNames[0];
        $module2 = $moduleNames[1];
        $alias1 = $modules[$module1]['config']['alias'] ?? strtolower($module1);
        $alias2 = $modules[$module2]['config']['alias'] ?? strtolower($module2);
        
        // Start with both enabled
        $this->testOrganization->enabled_modules = [$alias1, $alias2];
        $this->testOrganization->save();
        
        // Disable first module
        $this->postJson("/modules/{$module1}/toggle")->assertSuccessful();
        
        $this->testOrganization->refresh();
        
        // First should be disabled, second should remain enabled
        $this->assertNotContains($alias1, $this->testOrganization->enabled_modules);
        $this->assertContains($alias2, $this->testOrganization->enabled_modules);
    }

    // ==========================================
    // REGRESSION PREVENTION TESTS
    // ==========================================

    /**
     * This test specifically prevents the bug where moduleData['enabled'] 
     * was used instead of isEnabled(), causing toggles to not work.
     * 
     * @test
     */
    public function regression_toggle_does_not_use_module_json_enabled_state(): void
    {
        $this->authenticate();
        
        // The module.json files typically have "enabled": false by default
        // But the organization has the module enabled
        $this->testOrganization->enabled_modules = [$this->testModuleAlias];
        $this->testOrganization->save();
        
        // Get the module data (which would have 'enabled' => false from module.json)
        $moduleData = $this->moduleManager->all()[$this->testModuleName] ?? null;
        
        // Regardless of what module.json says, isEnabled should return true
        // because the organization has it enabled
        $this->assertTrue($this->moduleManager->isEnabled($this->testModuleName));
        
        // Toggle should DISABLE it (not enable based on module.json's false)
        $response = $this->postJson("/modules/{$this->testModuleName}/toggle");
        
        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
            'message' => 'Module disabled successfully',
        ]);
        
        // Verify it's actually disabled
        $this->testOrganization->refresh();
        $this->assertNotContains($this->testModuleAlias, $this->testOrganization->enabled_modules ?? []);
    }

    /**
     * Ensures that new modules added to the system work correctly with toggle.
     * 
     * @test
     */
    public function new_modules_integrate_correctly_with_toggle_system(): void
    {
        $this->authenticate();
        
        // Get all available modules
        $modules = $this->moduleManager->all();
        
        // Test at least one module that has no dependencies
        $testedCount = 0;
        
        foreach (array_keys($modules) as $moduleName) {
            $module = $modules[$moduleName];
            $alias = $module['config']['alias'] ?? strtolower($moduleName);
            
            // Skip if module has always_enabled flag
            if (!empty($module['config']['always_enabled'])) {
                continue;
            }
            
            // Skip if module has dependencies (to avoid dependency errors)
            $dependencies = $module['config']['dependencies'] ?? [];
            if (!empty($dependencies)) {
                continue;
            }
            
            // Start with module disabled
            $this->testOrganization->enabled_modules = [];
            $this->testOrganization->save();
            
            // Should be able to enable
            $response = $this->postJson("/modules/{$moduleName}/toggle");
            
            // If 422, it might have unmet dependencies or other constraints - skip
            if ($response->status() === 422) {
                continue;
            }
            
            $response->assertSuccessful();
            
            $this->testOrganization->refresh();
            $this->assertContains($alias, $this->testOrganization->enabled_modules, "Failed to enable {$moduleName}");
            
            // Should be able to disable
            $response = $this->postJson("/modules/{$moduleName}/toggle");
            $response->assertSuccessful();
            
            $this->testOrganization->refresh();
            $this->assertNotContains($alias, $this->testOrganization->enabled_modules ?? [], "Failed to disable {$moduleName}");
            
            $testedCount++;
        }
        
        // Ensure we tested at least one module
        $this->assertGreaterThan(0, $testedCount, 'Should have tested at least one module');
    }
}
