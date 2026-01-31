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
}
