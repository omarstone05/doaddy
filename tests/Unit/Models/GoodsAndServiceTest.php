<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\GoodsAndService;
use App\Models\StockMovement;
use App\Models\InvoiceItem;
use App\Models\QuoteItem;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Customer;
use Illuminate\Support\Str;

class GoodsAndServiceTest extends TestCase
{
    /** @test */
    public function it_belongs_to_organization(): void
    {
        $product = GoodsAndService::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $this->assertEquals($this->testOrganization->id, $product->organization_id);
    }

    /** @test */
    public function it_detects_low_stock_correctly(): void
    {
        $lowStockProduct = GoodsAndService::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'track_stock' => true,
            'current_stock' => 5,
            'minimum_stock' => 10,
        ]);

        $adequateStockProduct = GoodsAndService::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'track_stock' => true,
            'current_stock' => 20,
            'minimum_stock' => 10,
        ]);

        $noStockTrackingProduct = GoodsAndService::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'track_stock' => false,
            'current_stock' => 0,
            'minimum_stock' => 10,
        ]);

        $this->assertTrue($lowStockProduct->isLowStock());
        $this->assertTrue($lowStockProduct->is_low_stock);
        $this->assertFalse($adequateStockProduct->isLowStock());
        $this->assertFalse($noStockTrackingProduct->isLowStock());
    }

    /** @test */
    public function it_detects_low_stock_at_exactly_minimum(): void
    {
        $product = GoodsAndService::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'track_stock' => true,
            'current_stock' => 10,
            'minimum_stock' => 10,
        ]);

        // At exactly minimum, should be considered low stock
        $this->assertTrue($product->isLowStock());
    }

    /** @test */
    public function it_has_many_stock_movements(): void
    {
        $product = GoodsAndService::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        StockMovement::factory()->count(3)->create([
            'organization_id' => $this->testOrganization->id,
            'goods_service_id' => $product->id,
        ]);

        $this->assertCount(3, $product->stockMovements);
    }

    /** @test */
    public function it_has_many_invoice_items(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $product = GoodsAndService::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        InvoiceItem::create([
            'id' => (string) Str::uuid(),
            'invoice_id' => $invoice->id,
            'goods_service_id' => $product->id,
            'name' => $product->name,
            'quantity' => 2,
            'unit_price' => $product->selling_price,
            'total' => 2 * $product->selling_price,
        ]);

        $this->assertCount(1, $product->invoiceItems);
    }

    /** @test */
    public function it_has_many_quote_items(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $product = GoodsAndService::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $quote = Quote::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        QuoteItem::create([
            'id' => (string) Str::uuid(),
            'quote_id' => $quote->id,
            'goods_service_id' => $product->id,
            'name' => $product->name,
            'quantity' => 3,
            'unit_price' => $product->selling_price,
            'total' => 3 * $product->selling_price,
        ]);

        $this->assertCount(1, $product->quoteItems);
    }

    /** @test */
    public function it_checks_if_product_has_been_used(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $usedProduct = GoodsAndService::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $unusedProduct = GoodsAndService::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        InvoiceItem::create([
            'id' => (string) Str::uuid(),
            'invoice_id' => $invoice->id,
            'goods_service_id' => $usedProduct->id,
            'name' => $usedProduct->name,
            'quantity' => 1,
            'unit_price' => 100,
            'total' => 100,
        ]);

        $this->assertTrue($usedProduct->hasBeenUsed());
        $this->assertFalse($unusedProduct->hasBeenUsed());
    }

    /** @test */
    public function it_casts_prices_as_decimal(): void
    {
        $product = GoodsAndService::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'cost_price' => 99.99,
            'selling_price' => 149.99,
        ]);

        $this->assertEquals('99.99', $product->cost_price);
        $this->assertEquals('149.99', $product->selling_price);
    }

    /** @test */
    public function it_casts_stock_as_decimal(): void
    {
        $product = GoodsAndService::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'current_stock' => 100.5,
            'minimum_stock' => 10.0,
        ]);

        $this->assertEquals('100.50', $product->current_stock);
        $this->assertEquals('10.00', $product->minimum_stock);
    }

    /** @test */
    public function it_casts_booleans_correctly(): void
    {
        $activeProduct = GoodsAndService::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'is_active' => true,
            'track_stock' => true,
        ]);

        $inactiveProduct = GoodsAndService::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'is_active' => false,
            'track_stock' => false,
        ]);

        $this->assertTrue($activeProduct->is_active);
        $this->assertTrue($activeProduct->track_stock);
        $this->assertFalse($inactiveProduct->is_active);
        $this->assertFalse($inactiveProduct->track_stock);
    }

    /** @test */
    public function it_handles_product_and_service_types(): void
    {
        $product = GoodsAndService::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'product',
        ]);

        $service = GoodsAndService::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'service',
        ]);

        $this->assertEquals('product', $product->type);
        $this->assertEquals('service', $service->type);
    }

    /** @test */
    public function it_stores_sku_and_barcode(): void
    {
        $product = GoodsAndService::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'sku' => 'SKU-12345',
            'barcode' => '1234567890123',
        ]);

        $this->assertEquals('SKU-12345', $product->sku);
        $this->assertEquals('1234567890123', $product->barcode);
    }

    /** @test */
    public function it_handles_depreciation_fields(): void
    {
        $asset = GoodsAndService::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'product',
            'purchase_date' => '2024-01-15',
            'purchase_price' => 5000,
            'depreciation_method' => 'straight_line',
            'useful_life_years' => 5,
            'salvage_value' => 500,
            'accumulated_depreciation' => 900,
            'last_depreciation_date' => '2024-12-31',
        ]);

        $this->assertEquals('straight_line', $asset->depreciation_method);
        $this->assertEquals(5, $asset->useful_life_years);
        $this->assertEquals('500.00', $asset->salvage_value);
        $this->assertEquals('900.00', $asset->accumulated_depreciation);
        $this->assertInstanceOf(\Carbon\Carbon::class, $asset->last_depreciation_date);
    }

    /** @test */
    public function it_respects_organization_isolation(): void
    {
        $otherOrg = $this->createOtherOrganization();

        GoodsAndService::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'name' => 'Test Org Product',
        ]);

        GoodsAndService::factory()->create([
            'organization_id' => $otherOrg->id,
            'name' => 'Other Org Product',
        ]);

        $testOrgProducts = GoodsAndService::where('organization_id', $this->testOrganization->id)->get();
        $otherOrgProducts = GoodsAndService::where('organization_id', $otherOrg->id)->get();

        $this->assertCount(1, $testOrgProducts);
        $this->assertCount(1, $otherOrgProducts);
        $this->assertEquals('Test Org Product', $testOrgProducts->first()->name);
    }

    /** @test */
    public function it_can_filter_active_products(): void
    {
        GoodsAndService::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'is_active' => true,
        ]);

        GoodsAndService::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'is_active' => false,
        ]);

        $activeProducts = GoodsAndService::where('organization_id', $this->testOrganization->id)
            ->where('is_active', true)
            ->get();

        $this->assertCount(1, $activeProducts);
    }

    /** @test */
    public function it_stores_category(): void
    {
        $product = GoodsAndService::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'category' => 'Electronics',
        ]);

        $this->assertEquals('Electronics', $product->category);
    }

    /** @test */
    public function it_stores_unit(): void
    {
        $product = GoodsAndService::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'unit' => 'kg',
        ]);

        $this->assertEquals('kg', $product->unit);
    }
}
