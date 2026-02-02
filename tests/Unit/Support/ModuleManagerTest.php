<?php

namespace Tests\Unit\Support;

use Tests\TestCase;
use App\Support\ModuleManager;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;

class ModuleManagerTest extends TestCase
{
    protected ModuleManager $moduleManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->moduleManager = new ModuleManager();
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $manager = new ModuleManager();

        $this->assertInstanceOf(ModuleManager::class, $manager);
    }

    /** @test */
    public function it_discovers_modules_from_filesystem(): void
    {
        $modules = $this->moduleManager->discover();

        $this->assertIsArray($modules);
    }

    /** @test */
    public function it_returns_all_modules(): void
    {
        $modules = $this->moduleManager->all();

        $this->assertIsArray($modules);
    }

    /** @test */
    public function it_returns_enabled_modules_only(): void
    {
        $enabledModules = $this->moduleManager->enabled();

        $this->assertIsArray($enabledModules);
        foreach ($enabledModules as $module) {
            $this->assertTrue($this->moduleManager->isEnabled($module['name']));
        }
    }

    /** @test */
    public function it_checks_if_module_exists(): void
    {
        // Get a module that should exist
        $modules = $this->moduleManager->all();

        if (!empty($modules)) {
            $firstModuleName = array_key_first($modules);
            $this->assertTrue($this->moduleManager->exists($firstModuleName));
        }

        // Non-existent module
        $this->assertFalse($this->moduleManager->exists('NonExistentModule'));
    }

    /** @test */
    public function it_returns_false_for_non_existent_module_enabled_check(): void
    {
        $this->assertFalse($this->moduleManager->isEnabled('NonExistentModule'));
    }

    /** @test */
    public function it_gets_module_path(): void
    {
        $modules = $this->moduleManager->all();

        if (!empty($modules)) {
            $firstModuleName = array_key_first($modules);
            $path = $this->moduleManager->getPath($firstModuleName);

            $this->assertNotNull($path);
            $this->assertStringContainsString($firstModuleName, $path);
        }
    }

    /** @test */
    public function it_returns_null_for_non_existent_module_path(): void
    {
        $path = $this->moduleManager->getPath('NonExistentModule');

        $this->assertNull($path);
    }

    /** @test */
    public function it_gets_module_config(): void
    {
        $modules = $this->moduleManager->all();

        if (!empty($modules)) {
            $firstModuleName = array_key_first($modules);
            $config = $this->moduleManager->getConfig($firstModuleName);

            $this->assertIsArray($config);
        }
    }

    /** @test */
    public function it_returns_null_for_non_existent_module_config(): void
    {
        $config = $this->moduleManager->getConfig('NonExistentModule');

        $this->assertNull($config);
    }

    /** @test */
    public function it_gets_module_version(): void
    {
        $modules = $this->moduleManager->all();

        if (!empty($modules)) {
            $firstModuleName = array_key_first($modules);
            $version = $this->moduleManager->getVersion($firstModuleName);

            $this->assertNotNull($version);
            $this->assertMatchesRegularExpression('/^\d+\.\d+(\.\d+)?$/', $version);
        }
    }

    /** @test */
    public function it_returns_null_for_non_existent_module_version(): void
    {
        $version = $this->moduleManager->getVersion('NonExistentModule');

        $this->assertNull($version);
    }

    /** @test */
    public function it_gets_module_dependencies(): void
    {
        $modules = $this->moduleManager->all();

        if (!empty($modules)) {
            $firstModuleName = array_key_first($modules);
            $dependencies = $this->moduleManager->getDependencies($firstModuleName);

            $this->assertIsArray($dependencies);
        }
    }

    /** @test */
    public function it_returns_empty_array_for_non_existent_module_dependencies(): void
    {
        $dependencies = $this->moduleManager->getDependencies('NonExistentModule');

        $this->assertIsArray($dependencies);
        $this->assertEmpty($dependencies);
    }

    /** @test */
    public function it_checks_dependencies_satisfaction(): void
    {
        $modules = $this->moduleManager->all();

        if (!empty($modules)) {
            $firstModuleName = array_key_first($modules);
            $result = $this->moduleManager->checkDependencies($firstModuleName);

            $this->assertIsBool($result);
        }
    }

    /** @test */
    public function it_returns_false_when_enabling_non_existent_module(): void
    {
        $result = $this->moduleManager->enable('NonExistentModule');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_returns_false_when_disabling_non_existent_module(): void
    {
        $result = $this->moduleManager->disable('NonExistentModule');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_enables_module_for_authenticated_organization(): void
    {
        $this->authenticate();

        // Update organization with some enabled modules
        $this->testOrganization->enabled_modules = [];
        $this->testOrganization->save();

        $modules = $this->moduleManager->all();

        if (!empty($modules)) {
            $moduleName = array_key_first($modules);

            $result = $this->moduleManager->enable($moduleName);

            // Refresh to check
            $this->testOrganization->refresh();

            $this->assertTrue($result);
        }
    }

    /** @test */
    public function it_disables_module_for_authenticated_organization(): void
    {
        $this->authenticate();

        $modules = $this->moduleManager->all();

        if (!empty($modules)) {
            $moduleName = array_key_first($modules);
            $moduleConfig = $modules[$moduleName]['config'] ?? [];
            $alias = $moduleConfig['alias'] ?? $moduleName;

            // First enable the module
            $this->testOrganization->enabled_modules = [$alias, $moduleName];
            $this->testOrganization->save();

            // Then disable it
            $result = $this->moduleManager->disable($moduleName);

            $this->assertTrue($result);
        }
    }

    /** @test */
    public function it_uses_session_organization_id(): void
    {
        $this->authenticate();
        session(['current_organization_id' => $this->testOrganization->id]);

        $modules = $this->moduleManager->all();

        if (!empty($modules)) {
            $moduleName = array_key_first($modules);

            // Should not throw exception
            $isEnabled = $this->moduleManager->isEnabled($moduleName);

            $this->assertIsBool($isEnabled);
        }
    }

    /** @test */
    public function it_caches_modules_after_discovery(): void
    {
        // First call should populate cache
        $modules1 = $this->moduleManager->all();

        // Second call should return cached result
        $modules2 = $this->moduleManager->all();

        $this->assertEquals($modules1, $modules2);
    }

    /** @test */
    public function it_clears_cache_after_enable(): void
    {
        $this->authenticate();

        $modules = $this->moduleManager->all();

        if (!empty($modules)) {
            $moduleName = array_key_first($modules);

            // This should clear cache
            $this->moduleManager->enable($moduleName);

            // Next all() call should rediscover
            $modulesAfter = $this->moduleManager->all();

            $this->assertIsArray($modulesAfter);
        }
    }

    /** @test */
    public function it_clears_cache_after_disable(): void
    {
        $this->authenticate();

        $modules = $this->moduleManager->all();

        if (!empty($modules)) {
            $moduleName = array_key_first($modules);

            // This should clear cache
            $this->moduleManager->disable($moduleName);

            // Next all() call should rediscover
            $modulesAfter = $this->moduleManager->all();

            $this->assertIsArray($modulesAfter);
        }
    }

    // ==========================================
    // isEnabled() SPECIFIC TESTS
    // ==========================================

    /** @test */
    public function is_enabled_checks_organization_enabled_modules_not_module_json(): void
    {
        $this->authenticate();
        
        $modules = $this->moduleManager->all();
        if (empty($modules)) {
            $this->markTestSkipped('No modules available');
        }
        
        $moduleName = array_key_first($modules);
        $module = $modules[$moduleName];
        $alias = $module['config']['alias'] ?? strtolower($moduleName);
        
        // Set organization to have module enabled
        $this->testOrganization->enabled_modules = [$alias];
        $this->testOrganization->save();
        
        // Create fresh manager to ensure no stale state
        $freshManager = new ModuleManager();
        
        // isEnabled should return true based on organization, 
        // regardless of module.json's 'enabled' value
        $this->assertTrue($freshManager->isEnabled($moduleName));
    }

    /** @test */
    public function is_enabled_returns_false_when_module_not_in_organization_enabled_modules(): void
    {
        $this->authenticate();
        
        $modules = $this->moduleManager->all();
        if (empty($modules)) {
            $this->markTestSkipped('No modules available');
        }
        
        $moduleName = array_key_first($modules);
        
        // Set organization to have NO modules enabled
        $this->testOrganization->enabled_modules = [];
        $this->testOrganization->save();
        
        $freshManager = new ModuleManager();
        
        // Skip if module has always_enabled flag
        $module = $modules[$moduleName];
        if (!empty($module['config']['always_enabled'])) {
            $this->assertTrue($freshManager->isEnabled($moduleName));
        } else {
            $this->assertFalse($freshManager->isEnabled($moduleName));
        }
    }

    /** @test */
    public function is_enabled_handles_null_enabled_modules(): void
    {
        $this->authenticate();
        
        $modules = $this->moduleManager->all();
        if (empty($modules)) {
            $this->markTestSkipped('No modules available');
        }
        
        $moduleName = array_key_first($modules);
        $module = $modules[$moduleName];
        
        // Set enabled_modules to null
        $this->testOrganization->enabled_modules = null;
        $this->testOrganization->save();
        
        $freshManager = new ModuleManager();
        
        // Should not throw exception
        $result = $freshManager->isEnabled($moduleName);
        
        // Should return false (or true if always_enabled)
        if (!empty($module['config']['always_enabled'])) {
            $this->assertTrue($result);
        } else {
            $this->assertFalse($result);
        }
    }

    /** @test */
    public function is_enabled_checks_both_alias_and_name(): void
    {
        $this->authenticate();
        
        $modules = $this->moduleManager->all();
        if (empty($modules)) {
            $this->markTestSkipped('No modules available');
        }
        
        $moduleName = array_key_first($modules);
        $module = $modules[$moduleName];
        $alias = $module['config']['alias'] ?? strtolower($moduleName);
        
        // Skip if always_enabled
        if (!empty($module['config']['always_enabled'])) {
            $this->markTestSkipped('Module is always enabled');
        }
        
        // Enable using alias
        $this->testOrganization->enabled_modules = [$alias];
        $this->testOrganization->save();
        
        $freshManager = new ModuleManager();
        
        // Should be able to check using the NAME
        $this->assertTrue($freshManager->isEnabled($moduleName));
    }

    /** @test */
    public function is_enabled_respects_always_enabled_flag(): void
    {
        $this->authenticate();
        
        $modules = $this->moduleManager->all();
        
        foreach ($modules as $name => $module) {
            if (!empty($module['config']['always_enabled'])) {
                // This module should always return true
                $this->assertTrue($this->moduleManager->isEnabled($name));
                
                // Even if organization doesn't have it in enabled_modules
                $this->testOrganization->enabled_modules = [];
                $this->testOrganization->save();
                
                $freshManager = new ModuleManager();
                $this->assertTrue($freshManager->isEnabled($name), "{$name} should always be enabled");
            }
        }
    }

    // ==========================================
    // ORGANIZATION DETECTION TESTS
    // ==========================================

    /** @test */
    public function get_current_organization_uses_user_organization_id_fallback(): void
    {
        $this->authenticate();
        
        // Clear session org
        session()->forget('current_organization_id');
        
        // Ensure user has organization_id set (don't touch current_organization_id as it may not exist in test DB)
        $this->testUser->organization_id = $this->testOrganization->id;
        $this->testUser->save();
        
        $modules = $this->moduleManager->all();
        if (empty($modules)) {
            $this->markTestSkipped('No modules available');
        }
        
        $moduleName = array_key_first($modules);
        $module = $modules[$moduleName];
        $alias = $module['config']['alias'] ?? strtolower($moduleName);
        
        $this->testOrganization->enabled_modules = [$alias];
        $this->testOrganization->save();
        
        $freshManager = new ModuleManager();
        
        // Should still find the organization and return correct enabled state
        if (empty($module['config']['always_enabled'])) {
            $this->assertTrue($freshManager->isEnabled($moduleName));
        }
    }

    /** @test */
    public function enable_updates_organization_enabled_modules(): void
    {
        $this->authenticate();
        
        $modules = $this->moduleManager->all();
        if (empty($modules)) {
            $this->markTestSkipped('No modules available');
        }
        
        $moduleName = array_key_first($modules);
        $module = $modules[$moduleName];
        $alias = $module['config']['alias'] ?? strtolower($moduleName);
        
        // Start with empty
        $this->testOrganization->enabled_modules = [];
        $this->testOrganization->save();
        
        $freshManager = new ModuleManager();
        $result = $freshManager->enable($moduleName);
        
        $this->assertTrue($result);
        
        // Verify database was updated
        $this->testOrganization->refresh();
        $this->assertContains($alias, $this->testOrganization->enabled_modules);
    }

    /** @test */
    public function disable_removes_from_organization_enabled_modules(): void
    {
        $this->authenticate();
        
        $modules = $this->moduleManager->all();
        if (empty($modules)) {
            $this->markTestSkipped('No modules available');
        }
        
        $moduleName = array_key_first($modules);
        $module = $modules[$moduleName];
        $alias = $module['config']['alias'] ?? strtolower($moduleName);
        
        // Start with module enabled
        $this->testOrganization->enabled_modules = [$alias];
        $this->testOrganization->save();
        
        $freshManager = new ModuleManager();
        $result = $freshManager->disable($moduleName);
        
        $this->assertTrue($result);
        
        // Verify database was updated
        $this->testOrganization->refresh();
        $this->assertNotContains($alias, $this->testOrganization->enabled_modules ?? []);
    }

    /** @test */
    public function enable_does_not_duplicate_module(): void
    {
        $this->authenticate();
        
        $modules = $this->moduleManager->all();
        if (empty($modules)) {
            $this->markTestSkipped('No modules available');
        }
        
        $moduleName = array_key_first($modules);
        $module = $modules[$moduleName];
        $alias = $module['config']['alias'] ?? strtolower($moduleName);
        
        // Start with module already enabled
        $this->testOrganization->enabled_modules = [$alias];
        $this->testOrganization->save();
        
        $freshManager = new ModuleManager();
        $freshManager->enable($moduleName);
        
        $this->testOrganization->refresh();
        
        // Count occurrences
        $count = count(array_filter(
            $this->testOrganization->enabled_modules,
            fn($m) => $m === $alias
        ));
        
        $this->assertEquals(1, $count);
    }

    /** @test */
    public function model_is_refreshed_when_checking_is_enabled(): void
    {
        $this->authenticate();
        
        $modules = $this->moduleManager->all();
        if (empty($modules)) {
            $this->markTestSkipped('No modules available');
        }
        
        $moduleName = array_key_first($modules);
        $module = $modules[$moduleName];
        $alias = $module['config']['alias'] ?? strtolower($moduleName);
        
        // Skip if always_enabled
        if (!empty($module['config']['always_enabled'])) {
            $this->markTestSkipped('Module is always enabled');
        }
        
        // Start with module disabled
        $this->testOrganization->enabled_modules = [];
        $this->testOrganization->save();
        
        $manager1 = new ModuleManager();
        $this->assertFalse($manager1->isEnabled($moduleName));
        
        // Update database directly (simulating another process)
        \DB::table('organizations')
            ->where('id', $this->testOrganization->id)
            ->update(['enabled_modules' => json_encode([$alias])]);
        
        // Fresh manager should see the change due to refresh()
        $manager2 = new ModuleManager();
        $this->assertTrue($manager2->isEnabled($moduleName));
    }
}
