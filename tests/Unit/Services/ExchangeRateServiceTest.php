<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Currency\ExchangeRateService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ExchangeRateServiceTest extends TestCase
{
    protected ExchangeRateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExchangeRateService();
        Cache::flush(); // Clear cache before each test
    }

    /** @test */
    public function it_returns_one_for_same_currency(): void
    {
        $rate = $this->service->getExchangeRate('USD', 'USD');

        $this->assertEquals(1.0, $rate);
    }

    /** @test */
    public function it_fetches_exchange_rate_from_api(): void
    {
        Http::fake([
            'api.exchangerate-api.com/*' => Http::response([
                'rates' => [
                    'ZMW' => 25.5,
                    'EUR' => 0.85,
                    'GBP' => 0.73,
                ],
            ], 200),
        ]);

        $rate = $this->service->getExchangeRate('USD', 'ZMW');

        $this->assertEquals(25.5, $rate);
    }

    /** @test */
    public function it_caches_exchange_rates(): void
    {
        Http::fake([
            'api.exchangerate-api.com/*' => Http::response([
                'rates' => ['ZMW' => 25.5],
            ], 200),
        ]);

        // First call - should hit API
        $this->service->getExchangeRate('USD', 'ZMW');

        // Second call - should use cache
        $rate = $this->service->getExchangeRate('USD', 'ZMW');

        $this->assertEquals(25.5, $rate);
        Http::assertSentCount(1); // Only one API call should have been made
    }

    /** @test */
    public function it_converts_amount_correctly(): void
    {
        Http::fake([
            'api.exchangerate-api.com/*' => Http::response([
                'rates' => ['ZMW' => 25.0],
            ], 200),
        ]);

        $converted = $this->service->convert(100, 'USD', 'ZMW');

        $this->assertEquals(2500.00, $converted);
    }

    /** @test */
    public function it_returns_null_when_conversion_fails(): void
    {
        Http::fake([
            'api.exchangerate-api.com/*' => Http::response([], 500),
        ]);

        $converted = $this->service->convert(100, 'USD', 'ZMW');

        $this->assertNull($converted);
    }

    /** @test */
    public function it_gets_multiple_rates_at_once(): void
    {
        Http::fake([
            'api.exchangerate-api.com/*' => Http::response([
                'rates' => [
                    'ZMW' => 25.5,
                    'EUR' => 0.85,
                    'GBP' => 0.73,
                ],
            ], 200),
        ]);

        $rates = $this->service->getRates('USD', ['ZMW', 'EUR', 'GBP']);

        $this->assertArrayHasKey('ZMW', $rates);
        $this->assertArrayHasKey('EUR', $rates);
        $this->assertArrayHasKey('GBP', $rates);
        $this->assertEquals(25.5, $rates['ZMW']);
        $this->assertEquals(0.85, $rates['EUR']);
    }

    /** @test */
    public function it_returns_null_for_unknown_currency(): void
    {
        Http::fake([
            'api.exchangerate-api.com/*' => Http::response([
                'rates' => [
                    'ZMW' => 25.5,
                ],
            ], 200),
        ]);

        $rate = $this->service->getExchangeRate('USD', 'INVALID');

        $this->assertNull($rate);
    }

    /** @test */
    public function it_rounds_converted_amounts_to_two_decimals(): void
    {
        Http::fake([
            'api.exchangerate-api.com/*' => Http::response([
                'rates' => ['ZMW' => 25.333],
            ], 200),
        ]);

        $converted = $this->service->convert(100.55, 'USD', 'ZMW');

        // 100.55 * 25.333 = 2547.23315, rounded to 2547.23
        $this->assertEquals(2547.23, $converted);
    }

    /** @test */
    public function it_handles_api_timeout_gracefully(): void
    {
        Http::fake([
            'api.exchangerate-api.com/*' => Http::response([], 408), // Timeout
        ]);

        $rate = $this->service->getExchangeRate('USD', 'ZMW');

        $this->assertNull($rate);
    }
}
