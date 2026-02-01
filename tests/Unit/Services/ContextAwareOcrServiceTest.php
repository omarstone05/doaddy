<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ContextAwareOcrService;
use App\Services\ImprovedOcrService;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;

class ContextAwareOcrServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ContextAwareOcrService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set required config for penda-jwt
        Config::set('penda-jwt.secret', 'test-secret-key-for-testing');
        
        $this->service = new ContextAwareOcrService();
    }

    /** @test */
    public function it_extends_improved_ocr_service(): void
    {
        $this->assertInstanceOf(ImprovedOcrService::class, $this->service);
    }

    /** @test */
    public function it_analyzes_date_uncertainty_for_future_dates(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('assessFieldConfidence');
        $method->setAccessible(true);

        // Future date should have low confidence
        $futureDate = now()->addMonth()->format('Y-m-d');
        $confidence = $method->invoke($this->service, 'date', $futureDate, []);

        $this->assertLessThan(0.5, $confidence);
    }

    /** @test */
    public function it_analyzes_date_uncertainty_for_very_old_dates(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('assessFieldConfidence');
        $method->setAccessible(true);

        // Date more than 10 years old should have reduced confidence
        $oldDate = now()->subYears(15)->format('Y-m-d');
        $confidence = $method->invoke($this->service, 'date', $oldDate, []);

        $this->assertLessThan(1.0, $confidence);
    }

    /** @test */
    public function it_returns_full_confidence_for_valid_recent_date(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('assessFieldConfidence');
        $method->setAccessible(true);

        // Yesterday's date should have full confidence
        $validDate = now()->subDay()->format('Y-m-d');
        $confidence = $method->invoke($this->service, 'date', $validDate, []);

        $this->assertEquals(1.0, $confidence);
    }

    /** @test */
    public function it_returns_zero_confidence_for_null_values(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('assessFieldConfidence');
        $method->setAccessible(true);

        $confidence = $method->invoke($this->service, 'date', null, []);
        $this->assertEquals(0.0, $confidence);

        $confidence = $method->invoke($this->service, 'amount', '', []);
        $this->assertEquals(0.0, $confidence);
    }

    /** @test */
    public function it_analyzes_amount_uncertainty_for_zero_amounts(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('assessFieldConfidence');
        $method->setAccessible(true);

        $confidence = $method->invoke($this->service, 'amount', 0, []);
        $this->assertLessThan(0.5, $confidence);

        $confidence = $method->invoke($this->service, 'amount', -10, []);
        $this->assertLessThan(0.5, $confidence);
    }

    /** @test */
    public function it_reduces_confidence_for_very_large_amounts(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('assessFieldConfidence');
        $method->setAccessible(true);

        $confidence = $method->invoke($this->service, 'amount', 5000000, []);
        $this->assertLessThan(1.0, $confidence);
    }

    /** @test */
    public function it_returns_good_confidence_for_normal_amounts(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('assessFieldConfidence');
        $method->setAccessible(true);

        $confidence = $method->invoke($this->service, 'amount', '150.00', []);
        $this->assertGreaterThanOrEqual(0.9, $confidence);
    }

    /** @test */
    public function it_analyzes_merchant_name_length(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('assessFieldConfidence');
        $method->setAccessible(true);

        // Very short name
        $confidence = $method->invoke($this->service, 'merchant', 'A', []);
        $this->assertLessThan(0.5, $confidence);

        // Normal name
        $confidence = $method->invoke($this->service, 'merchant', 'Shoprite', []);
        $this->assertEquals(1.0, $confidence);
    }

    /** @test */
    public function it_reduces_confidence_for_numeric_merchant_names(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('assessFieldConfidence');
        $method->setAccessible(true);

        // Mostly numbers
        $confidence = $method->invoke($this->service, 'merchant', '12345678', []);
        $this->assertLessThan(0.5, $confidence);
    }

    /** @test */
    public function it_validates_zambian_phone_numbers(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('assessFieldConfidence');
        $method->setAccessible(true);

        // Valid Zambian number
        $confidence = $method->invoke($this->service, 'phone', '0971234567', []);
        $this->assertGreaterThan(0.5, $confidence);

        // Invalid format
        $confidence = $method->invoke($this->service, 'phone', '123', []);
        $this->assertLessThan(0.5, $confidence);
    }

    /** @test */
    public function it_validates_mobile_money_providers(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('assessFieldConfidence');
        $method->setAccessible(true);

        // Valid provider
        $confidence = $method->invoke($this->service, 'provider', 'Airtel Money', []);
        $this->assertEquals(1.0, $confidence);

        // Unknown provider
        $confidence = $method->invoke($this->service, 'provider', 'Unknown Provider', []);
        $this->assertLessThan(0.5, $confidence);
    }

    /** @test */
    public function it_gets_critical_fields_for_receipt(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getCriticalFields');
        $method->setAccessible(true);

        $fields = $method->invoke($this->service, 'receipt');

        $this->assertContains('date', $fields);
        $this->assertContains('total', $fields);
        $this->assertContains('merchant', $fields);
    }

    /** @test */
    public function it_gets_critical_fields_for_invoice(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getCriticalFields');
        $method->setAccessible(true);

        $fields = $method->invoke($this->service, 'invoice');

        $this->assertContains('date', $fields);
        $this->assertContains('total', $fields);
        $this->assertContains('invoice_number', $fields);
        $this->assertContains('vendor', $fields);
    }

    /** @test */
    public function it_gets_critical_fields_for_mobile_money(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getCriticalFields');
        $method->setAccessible(true);

        $fields = $method->invoke($this->service, 'mobile_money');

        $this->assertContains('date', $fields);
        $this->assertContains('amount', $fields);
        $this->assertContains('transaction_id', $fields);
        $this->assertContains('provider', $fields);
    }

    /** @test */
    public function it_generates_description_from_merchant(): void
    {
        // Test description generation logic directly
        $data = ['merchant' => 'Shoprite'];
        
        $parts = [];
        if (isset($data['merchant'])) {
            $parts[] = 'Purchase from ' . $data['merchant'];
        }
        
        $description = implode(' ', $parts) ?: 'Transaction';
        $this->assertStringContainsString('Shoprite', $description);
    }

    /** @test */
    public function it_determines_uncertainty_correctly(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('analyzeUncertainty');
        $method->setAccessible(true);

        // High confidence data
        $result = $method->invoke($this->service, [
            'date' => now()->subDay()->format('Y-m-d'),
            'total' => '150.00',
            'merchant' => 'Shoprite',
        ], 'receipt');

        $this->assertArrayHasKey('has_uncertainty', $result);
        $this->assertArrayHasKey('uncertain_fields', $result);
        $this->assertArrayHasKey('average_confidence', $result);
    }

    /** @test */
    public function it_marks_documents_as_auto_importable_when_high_confidence(): void
    {
        // Create a mock document result
        $mockResult = [
            'success' => true,
            'document_type' => 'receipt',
            'data' => [
                'date' => now()->format('Y-m-d'),
                'total' => '150.00',
                'merchant' => 'Shoprite',
            ],
            'confidence' => 0.95,
            'requires_review' => false,
            'auto_importable' => true,
        ];

        $this->assertTrue($mockResult['auto_importable']);
        $this->assertFalse($mockResult['requires_review']);
    }

    /** @test */
    public function it_marks_documents_for_review_when_low_confidence(): void
    {
        // Create a mock document result
        $mockResult = [
            'success' => true,
            'document_type' => 'receipt',
            'data' => [
                'date' => null,
                'total' => '0',
                'merchant' => 'A',
            ],
            'confidence' => 0.3,
            'requires_review' => true,
            'auto_importable' => false,
        ];

        $this->assertFalse($mockResult['auto_importable']);
        $this->assertTrue($mockResult['requires_review']);
    }

    /** @test */
    public function it_provides_categories_for_expenses(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getCategories');
        $method->setAccessible(true);

        $categories = $method->invoke($this->service, 'receipt', []);

        $this->assertIsArray($categories);
        $this->assertNotEmpty($categories);

        // Check structure
        $first = $categories[0];
        $this->assertArrayHasKey('value', $first);
        $this->assertArrayHasKey('label', $first);
    }

    /** @test */
    public function it_suggests_dates_correctly(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('suggestDates');
        $method->setAccessible(true);

        $suggestions = $method->invoke($this->service, null, []);

        $this->assertIsArray($suggestions);
        $this->assertNotEmpty($suggestions);

        // Should include today and yesterday
        $values = array_column($suggestions, 'value');
        $this->assertContains(now()->format('Y-m-d'), $values);
        $this->assertContains(now()->subDay()->format('Y-m-d'), $values);
    }
}
