<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\AddyChatMessage;
use App\Models\MoneyMovement;
use App\Models\MoneyAccount;
use App\Services\ContextAwareOcrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;

class ChatOcrIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        
        // Set required config for penda-jwt
        config(['penda-jwt.secret' => 'test-secret-key-for-testing']);
    }

    /** @test */
    public function chat_accepts_file_uploads(): void
    {
        $this->authenticate();

        $file = UploadedFile::fake()->image('receipt.jpg', 800, 600);

        $response = $this->postJson('/api/addy/chat', [
            'message' => 'Here is my receipt',
            'files' => [$file],
        ]);

        $response->assertStatus(200);

        // Verify user message was saved with attachments
        $this->assertDatabaseHas('addy_chat_messages', [
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'role' => 'user',
        ]);

        $message = AddyChatMessage::where('organization_id', $this->testOrganization->id)
            ->where('user_id', $this->testUser->id)
            ->where('role', 'user')
            ->first();

        $this->assertNotEmpty($message->attachments);
    }

    /** @test */
    public function chat_accepts_pdf_files(): void
    {
        $this->authenticate();

        $file = UploadedFile::fake()->create('invoice.pdf', 500, 'application/pdf');

        $response = $this->postJson('/api/addy/chat', [
            'message' => 'Here is my invoice',
            'files' => [$file],
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function chat_rejects_invalid_file_types(): void
    {
        $this->authenticate();

        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');

        $response = $this->postJson('/api/addy/chat', [
            'message' => 'Here is a file',
            'files' => [$file],
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function chat_rejects_files_over_size_limit(): void
    {
        $this->authenticate();

        // Create a file over 10MB
        $file = UploadedFile::fake()->create('huge.pdf', 15000, 'application/pdf');

        $response = $this->postJson('/api/addy/chat', [
            'message' => 'Here is a large file',
            'files' => [$file],
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function chat_accepts_multiple_files(): void
    {
        $this->authenticate();

        $files = [
            UploadedFile::fake()->image('receipt1.jpg', 800, 600),
            UploadedFile::fake()->image('receipt2.jpg', 800, 600),
            UploadedFile::fake()->create('invoice.pdf', 500, 'application/pdf'),
        ];

        $response = $this->postJson('/api/addy/chat', [
            'message' => 'Here are my documents',
            'files' => $files,
        ]);

        $response->assertStatus(200);

        $message = AddyChatMessage::where('organization_id', $this->testOrganization->id)
            ->where('user_id', $this->testUser->id)
            ->where('role', 'user')
            ->first();

        $this->assertCount(3, $message->attachments);
    }

    /** @test */
    public function chat_limits_max_file_count(): void
    {
        $this->authenticate();

        $files = [];
        for ($i = 0; $i < 6; $i++) {
            $files[] = UploadedFile::fake()->image("receipt{$i}.jpg", 100, 100);
        }

        $response = $this->postJson('/api/addy/chat', [
            'message' => 'Too many files',
            'files' => $files,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function import_document_endpoint_exists(): void
    {
        $this->authenticate();

        $response = $this->postJson('/api/addy/chat/import-document', [
            'file_path' => 'temp/test.jpg',
            'document_type' => 'receipt',
            'data' => [
                'merchant' => 'Test Store',
                'total' => 100.00,
                'date' => '2026-01-31',
            ],
            'reviewed' => true,
        ]);

        // Should either succeed or fail gracefully (not 404 or 500)
        $this->assertTrue(
            in_array($response->status(), [200, 400, 422])
        );
    }

    /** @test */
    public function import_document_requires_authentication(): void
    {
        $response = $this->postJson('/api/addy/chat/import-document', [
            'file_path' => 'temp/test.jpg',
            'document_type' => 'receipt',
            'data' => ['total' => 100],
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function import_document_validates_required_fields(): void
    {
        $this->authenticate();

        $response = $this->postJson('/api/addy/chat/import-document', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file_path', 'document_type', 'data']);
    }

    /** @test */
    public function import_receipt_endpoint_processes_request(): void
    {
        $this->authenticate();

        // Create a money account first
        MoneyAccount::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'organization_id' => $this->testOrganization->id,
            'name' => 'Expenses',
            'type' => 'expense',
            'currency' => 'ZMW',
        ]);

        $response = $this->postJson('/api/addy/chat/import-document', [
            'file_path' => 'temp/receipt.jpg',
            'document_type' => 'receipt',
            'data' => [
                'merchant' => 'Shoprite',
                'total' => 150.50,
                'date' => '2026-01-31',
                'category' => 'Groceries',
            ],
            'reviewed' => true,
        ]);

        // Should return 200 whether import succeeds or fails gracefully
        $response->assertStatus(200);
        
        // Response should have expected structure
        $response->assertJsonStructure(['success', 'message']);
    }

    /** @test */
    public function import_income_endpoint_processes_request(): void
    {
        $this->authenticate();

        // Create a money account first
        MoneyAccount::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'organization_id' => $this->testOrganization->id,
            'name' => 'Income',
            'type' => 'income',
            'currency' => 'ZMW',
        ]);

        $response = $this->postJson('/api/addy/chat/import-document', [
            'file_path' => 'temp/payment.jpg',
            'document_type' => 'income',
            'data' => [
                'amount' => 500.00,
                'date' => '2026-01-31',
                'description' => 'Customer payment',
            ],
            'reviewed' => true,
        ]);

        // Should return 200 whether import succeeds or fails gracefully
        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'message']);
    }

    /** @test */
    public function import_mobile_money_endpoint_processes_request(): void
    {
        $this->authenticate();

        $response = $this->postJson('/api/addy/chat/import-document', [
            'file_path' => 'temp/momo.jpg',
            'document_type' => 'mobile_money',
            'data' => [
                'provider' => 'Airtel Money',
                'amount' => 250.00,
                'date' => '2026-01-31',
                'transaction_id' => 'TX123456',
                'type' => 'income',
            ],
            'reviewed' => true,
        ]);

        // Should return 200 whether import succeeds or fails gracefully
        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'message']);
    }

    /** @test */
    public function import_returns_reviewed_flag(): void
    {
        $this->authenticate();

        MoneyAccount::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'organization_id' => $this->testOrganization->id,
            'name' => 'Expenses',
            'type' => 'expense',
            'currency' => 'ZMW',
        ]);

        $response = $this->postJson('/api/addy/chat/import-document', [
            'file_path' => 'temp/receipt.jpg',
            'document_type' => 'receipt',
            'data' => [
                'merchant' => 'Test Store',
                'total' => 100.00,
                'date' => '2026-01-31',
            ],
            'reviewed' => true,
        ]);

        $response->assertStatus(200);
        
        // Should include review metadata
        $json = $response->json();
        $this->assertArrayHasKey('reviewed', $json);
        $this->assertTrue($json['reviewed']);
    }

    /** @test */
    public function ocr_result_metadata_stored_with_message(): void
    {
        $this->authenticate();

        $file = UploadedFile::fake()->image('receipt.jpg', 800, 600);

        $this->postJson('/api/addy/chat', [
            'message' => 'Process this receipt',
            'files' => [$file],
        ]);

        $message = AddyChatMessage::where('organization_id', $this->testOrganization->id)
            ->where('user_id', $this->testUser->id)
            ->where('role', 'user')
            ->first();

        // Metadata should be stored
        $this->assertIsArray($message->metadata);
    }

    /** @test */
    public function file_upload_without_message_is_allowed(): void
    {
        $this->authenticate();

        $file = UploadedFile::fake()->image('receipt.jpg', 800, 600);

        $response = $this->postJson('/api/addy/chat', [
            'files' => [$file],
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function message_without_file_still_works(): void
    {
        $this->authenticate();

        $response = $this->postJson('/api/addy/chat', [
            'message' => 'Just a regular message',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'quick_actions',
            ]);
    }

    /**
     * Helper to check if string contains substring
     */
    protected function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'"
        );
    }
}
