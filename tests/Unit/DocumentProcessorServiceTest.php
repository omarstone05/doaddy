<?php

namespace Tests\Unit;

use App\Services\Addy\DocumentProcessorService;
use App\Services\AI\AIService;
use Tests\TestCase;

class DocumentProcessorServiceTest extends TestCase
{
    /** @test */
    public function it_parses_structured_data_from_ai_response(): void
    {
        $ai = new class extends AIService {
            public function __construct()
            {
                // Skip parent constructor to avoid config lookups during tests
            }

            public function ask(string $prompt, ?string $systemMessage = null): string
            {
                return json_encode([
                    'document_type' => 'invoice',
                    'type' => 'invoice',
                    'amount' => 750.00,
                    'currency' => 'ZMW',
                    'customer_name' => 'Brave Brands',
                    'date' => '2025-02-15',
                ]);
            }
        };

        $processor = new DocumentProcessorService($ai);

        $reflection = new \ReflectionClass($processor);
        $method = $reflection->getMethod('extractStructuredData');
        $method->setAccessible(true);

        $data = $method->invoke($processor, 'Invoice text', 'application/pdf');

        $this->assertEquals('invoice', $data['document_type']);
        $this->assertEquals(750.00, $data['amount']);
        $this->assertEquals('Brave Brands', $data['customer_name']);
    }
}
