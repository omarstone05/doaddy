<?php

namespace Tests\Feature;

use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test departments index is accessible
     */
    public function test_departments_index_is_accessible(): void
    {
        $response = $this->authenticate()->get('/departments');

        $response->assertStatus(200);
    }

    /**
     * Test department create page is accessible
     */
    public function test_department_create_page_is_accessible(): void
    {
        $response = $this->authenticate()->get('/departments/create');

        $response->assertStatus(200);
    }

    /**
     * Test department can be created
     */
    public function test_department_can_be_created(): void
    {
        $response = $this->authenticate()->postJson('/departments', [
            'name' => 'Engineering',
            'description' => 'Engineering department',
        ]);

        // Should redirect or succeed
        $this->assertTrue(
            $response->isRedirect() || $response->isSuccessful()
        );
    }

    /**
     * Test department can be viewed
     */
    public function test_department_can_be_viewed(): void
    {
        $department = Department::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $response = $this->authenticate()->get("/departments/{$department->id}");

        $response->assertStatus(200);
    }

    /**
     * Test department can be updated
     */
    public function test_department_can_be_updated(): void
    {
        $department = Department::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'name' => 'Old Name',
        ]);

        $response = $this->authenticate()->putJson("/departments/{$department->id}", [
            'name' => 'New Name',
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'name' => 'New Name',
        ]);
    }

    /**
     * Test department can be deleted
     */
    public function test_department_can_be_deleted(): void
    {
        $department = Department::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $response = $this->authenticate()->delete("/departments/{$department->id}");

        $response->assertRedirect();
    }

    /**
     * Test department requires name
     */
    public function test_department_requires_name(): void
    {
        $response = $this->authenticate()->postJson('/departments', [
            'description' => 'Test department',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }
}
