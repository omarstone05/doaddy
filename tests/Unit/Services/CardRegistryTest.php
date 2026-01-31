<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Dashboard\CardRegistry;

class CardRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clear registry before each test
        CardRegistry::clear();
    }

    /** @test */
    public function it_registers_a_card(): void
    {
        CardRegistry::register('finance', [
            'id' => 'finance.revenue',
            'name' => 'Revenue Card',
            'description' => 'Shows total revenue',
        ]);

        $card = CardRegistry::getCard('finance.revenue');

        $this->assertNotNull($card);
        $this->assertEquals('Revenue Card', $card['name']);
        $this->assertEquals('finance', $card['module_id']);
    }

    /** @test */
    public function it_registers_module_metadata(): void
    {
        CardRegistry::registerModule('finance', [
            'name' => 'Finance Module',
            'icon' => 'dollar-sign',
        ]);

        $modules = CardRegistry::getAllModules();
        $financeModule = collect($modules)->firstWhere('id', 'finance');

        $this->assertNotNull($financeModule);
        $this->assertEquals('Finance Module', $financeModule['name']);
    }

    /** @test */
    public function it_gets_all_cards(): void
    {
        CardRegistry::register('finance', [
            'id' => 'finance.revenue',
            'name' => 'Revenue',
        ]);

        CardRegistry::register('finance', [
            'id' => 'finance.expenses',
            'name' => 'Expenses',
        ]);

        CardRegistry::register('sales', [
            'id' => 'sales.pipeline',
            'name' => 'Sales Pipeline',
        ]);

        $allCards = CardRegistry::getAllCards();

        $this->assertCount(3, $allCards);
        $this->assertArrayHasKey('finance.revenue', $allCards);
        $this->assertArrayHasKey('finance.expenses', $allCards);
        $this->assertArrayHasKey('sales.pipeline', $allCards);
    }

    /** @test */
    public function it_gets_cards_by_module(): void
    {
        CardRegistry::register('finance', [
            'id' => 'finance.revenue',
            'name' => 'Revenue',
        ]);

        CardRegistry::register('finance', [
            'id' => 'finance.expenses',
            'name' => 'Expenses',
        ]);

        CardRegistry::register('sales', [
            'id' => 'sales.pipeline',
            'name' => 'Sales Pipeline',
        ]);

        $financeCards = CardRegistry::getModuleCards('finance');
        $salesCards = CardRegistry::getModuleCards('sales');

        $this->assertCount(2, $financeCards);
        $this->assertCount(1, $salesCards);
    }

    /** @test */
    public function it_returns_null_for_nonexistent_card(): void
    {
        $card = CardRegistry::getCard('nonexistent.card');

        $this->assertNull($card);
    }

    /** @test */
    public function it_gets_cards_by_category(): void
    {
        CardRegistry::register('finance', [
            'id' => 'finance.revenue',
            'name' => 'Revenue',
            'category' => 'metrics',
        ]);

        CardRegistry::register('finance', [
            'id' => 'finance.chart',
            'name' => 'Revenue Chart',
            'category' => 'charts',
        ]);

        CardRegistry::register('sales', [
            'id' => 'sales.metrics',
            'name' => 'Sales Metrics',
            'category' => 'metrics',
        ]);

        $metricCards = CardRegistry::getCardsByCategory('metrics');
        $chartCards = CardRegistry::getCardsByCategory('charts');

        $this->assertCount(2, $metricCards);
        $this->assertCount(1, $chartCards);
    }

    /** @test */
    public function it_searches_cards_by_name(): void
    {
        CardRegistry::register('finance', [
            'id' => 'finance.revenue',
            'name' => 'Revenue Overview',
            'description' => 'Shows total revenue',
        ]);

        CardRegistry::register('finance', [
            'id' => 'finance.expenses',
            'name' => 'Expense Tracker',
            'description' => 'Track all expenses',
        ]);

        $results = CardRegistry::searchCards('revenue');

        $this->assertCount(1, $results);
        $this->assertArrayHasKey('finance.revenue', $results);
    }

    /** @test */
    public function it_searches_cards_by_description(): void
    {
        CardRegistry::register('finance', [
            'id' => 'finance.revenue',
            'name' => 'Revenue',
            'description' => 'Shows monthly income trends',
        ]);

        CardRegistry::register('finance', [
            'id' => 'finance.expenses',
            'name' => 'Expenses',
            'description' => 'Track spending patterns',
        ]);

        $results = CardRegistry::searchCards('income');

        $this->assertCount(1, $results);
        $this->assertArrayHasKey('finance.revenue', $results);
    }

    /** @test */
    public function it_searches_cards_case_insensitively(): void
    {
        CardRegistry::register('finance', [
            'id' => 'finance.revenue',
            'name' => 'REVENUE',
        ]);

        $results = CardRegistry::searchCards('revenue');

        $this->assertCount(1, $results);
    }

    /** @test */
    public function it_gets_cards_grouped_by_module(): void
    {
        CardRegistry::registerModule('finance', ['name' => 'Finance']);
        CardRegistry::registerModule('sales', ['name' => 'Sales']);

        CardRegistry::register('finance', [
            'id' => 'finance.revenue',
            'name' => 'Revenue',
        ]);

        CardRegistry::register('sales', [
            'id' => 'sales.pipeline',
            'name' => 'Pipeline',
        ]);

        $grouped = CardRegistry::getCardsGroupedByModule();

        $this->assertArrayHasKey('finance', $grouped);
        $this->assertArrayHasKey('sales', $grouped);
        $this->assertEquals('Finance', $grouped['finance']['metadata']['name']);
        $this->assertCount(1, $grouped['finance']['cards']);
    }

    /** @test */
    public function it_gets_recommended_cards(): void
    {
        CardRegistry::register('retail', [
            'id' => 'retail.sales',
            'name' => 'Retail Sales',
            'suitable_for' => ['retail', 'general'],
            'priority' => 10,
        ]);

        CardRegistry::register('finance', [
            'id' => 'finance.revenue',
            'name' => 'Revenue',
            'suitable_for' => ['general'],
            'priority' => 5,
        ]);

        CardRegistry::register('manufacturing', [
            'id' => 'manufacturing.production',
            'name' => 'Production',
            'suitable_for' => ['manufacturing'],
            'priority' => 10,
        ]);

        $user = (object) ['business_type' => 'retail'];
        $recommended = CardRegistry::getRecommendedCards($user, 2);

        $this->assertCount(2, $recommended);
        // Retail sales should be first (matching business type + high priority)
        $this->assertEquals('retail.sales', $recommended[0]['id']);
    }

    /** @test */
    public function it_clears_all_registered_cards(): void
    {
        CardRegistry::register('finance', [
            'id' => 'finance.revenue',
            'name' => 'Revenue',
        ]);

        CardRegistry::registerModule('finance', ['name' => 'Finance']);

        CardRegistry::clear();

        $this->assertEmpty(CardRegistry::getAllCards());
        $this->assertEmpty(CardRegistry::getAllModules());
    }

    /** @test */
    public function it_counts_cards_per_module(): void
    {
        CardRegistry::registerModule('finance', ['name' => 'Finance']);

        CardRegistry::register('finance', [
            'id' => 'finance.revenue',
            'name' => 'Revenue',
        ]);

        CardRegistry::register('finance', [
            'id' => 'finance.expenses',
            'name' => 'Expenses',
        ]);

        $modules = CardRegistry::getAllModules();
        $financeModule = collect($modules)->firstWhere('id', 'finance');

        $this->assertEquals(2, $financeModule['card_count']);
    }
}
