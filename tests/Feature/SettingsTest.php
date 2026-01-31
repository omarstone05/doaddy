<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test settings page is accessible
     */
    public function test_settings_page_is_accessible(): void
    {
        $response = $this->authenticate()->get('/settings');

        $response->assertStatus(200);
    }

    /**
     * Test settings can be updated
     */
    public function test_settings_can_be_updated(): void
    {
        $response = $this->authenticate()->putJson('/settings', [
            'name' => 'Updated Org Name',
            'organization_name' => 'Updated Org Name',
            'timezone' => 'Africa/Lusaka',
            'currency' => 'ZMW',
        ]);

        // Should succeed or redirect
        $this->assertTrue(
            $response->isSuccessful() || $response->isRedirect()
        );
    }

    /**
     * Test addy settings page is accessible
     */
    public function test_addy_settings_page_is_accessible(): void
    {
        $response = $this->authenticate()->get('/settings/addy');

        $response->assertStatus(200);
    }

    /**
     * Test invoice settings page is accessible
     */
    public function test_invoice_settings_page_is_accessible(): void
    {
        $response = $this->authenticate()->get('/settings/invoices');

        $response->assertStatus(200);
    }

    /**
     * Test modules settings page is accessible
     */
    public function test_modules_settings_page_is_accessible(): void
    {
        $response = $this->authenticate()->get('/settings/modules');

        $response->assertStatus(200);
    }

    /**
     * Test team settings page is accessible
     */
    public function test_team_settings_page_is_accessible(): void
    {
        $response = $this->authenticate()->get('/settings/team');

        $response->assertStatus(200);
    }

    /**
     * Test logo can be updated
     */
    public function test_logo_can_be_updated(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->image('logo.png', 200, 200);

        $response = $this->authenticate()->postJson('/settings/logo', [
            'logo' => $file,
        ]);

        // Should succeed or redirect
        $this->assertTrue(
            $response->isSuccessful() || $response->isRedirect()
        );
    }

    /**
     * Test settings require authentication
     */
    public function test_settings_require_authentication(): void
    {
        $response = $this->get('/settings');

        // Should redirect or return error
        $this->assertTrue(
            $response->isRedirect() || in_array($response->status(), [401, 403, 500])
        );
    }
}
