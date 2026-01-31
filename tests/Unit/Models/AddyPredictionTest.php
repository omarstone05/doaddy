<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\AddyPrediction;
use App\Models\Organization;
use Carbon\Carbon;

class AddyPredictionTest extends TestCase
{
    /** @test */
    public function it_can_create_prediction(): void
    {
        $prediction = AddyPrediction::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'monthly_revenue',
            'category' => 'money',
            'prediction_date' => now(),
            'target_date' => now()->addMonth(),
            'predicted_value' => 50000.00,
            'confidence' => 0.85,
        ]);

        $this->assertDatabaseHas('addy_predictions', [
            'id' => $prediction->id,
            'organization_id' => $this->testOrganization->id,
            'type' => 'monthly_revenue',
        ]);
    }

    /** @test */
    public function it_belongs_to_organization(): void
    {
        $prediction = AddyPrediction::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'monthly_revenue',
            'category' => 'money',
            'prediction_date' => now(),
            'target_date' => now()->addMonth(),
            'predicted_value' => 50000.00,
        ]);

        $this->assertInstanceOf(Organization::class, $prediction->organization);
        $this->assertEquals($this->testOrganization->id, $prediction->organization->id);
    }

    /** @test */
    public function it_casts_date_fields(): void
    {
        $prediction = AddyPrediction::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'monthly_revenue',
            'category' => 'money',
            'prediction_date' => '2025-01-20',
            'target_date' => '2025-02-28',
            'predicted_value' => 50000.00,
        ]);

        $this->assertInstanceOf(Carbon::class, $prediction->prediction_date);
        $this->assertInstanceOf(Carbon::class, $prediction->target_date);
    }

    /** @test */
    public function it_casts_factors_to_array(): void
    {
        $factors = [
            'historical_trend' => 0.3,
            'seasonal_adjustment' => 0.2,
            'market_conditions' => 0.15,
        ];

        $prediction = AddyPrediction::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'monthly_revenue',
            'category' => 'money',
            'prediction_date' => now(),
            'target_date' => now()->addMonth(),
            'predicted_value' => 50000.00,
            'factors' => $factors,
        ]);

        $this->assertIsArray($prediction->factors);
        $this->assertEquals(0.3, $prediction->factors['historical_trend']);
    }

    /** @test */
    public function it_casts_metadata_to_array(): void
    {
        $metadata = [
            'model_version' => '1.2',
            'training_data_period' => '12_months',
        ];

        $prediction = AddyPrediction::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'monthly_revenue',
            'category' => 'money',
            'prediction_date' => now(),
            'target_date' => now()->addMonth(),
            'predicted_value' => 50000.00,
            'metadata' => $metadata,
        ]);

        $this->assertIsArray($prediction->metadata);
        $this->assertEquals('1.2', $prediction->metadata['model_version']);
    }

    /** @test */
    public function get_latest_returns_most_recent_prediction(): void
    {
        AddyPrediction::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'monthly_revenue',
            'category' => 'money',
            'prediction_date' => now()->subDays(5),
            'target_date' => now()->addMonth(),
            'predicted_value' => 45000.00,
        ]);

        $latest = AddyPrediction::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'monthly_revenue',
            'category' => 'money',
            'prediction_date' => now(),
            'target_date' => now()->addMonth(),
            'predicted_value' => 50000.00,
        ]);

        AddyPrediction::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'monthly_revenue',
            'category' => 'money',
            'prediction_date' => now()->subDays(10),
            'target_date' => now()->addMonth(),
            'predicted_value' => 40000.00,
        ]);

        $result = AddyPrediction::getLatest($this->testOrganization->id, 'monthly_revenue');

        $this->assertEquals($latest->id, $result->id);
        $this->assertEquals(50000.00, (float) $result->predicted_value);
    }

    /** @test */
    public function get_latest_filters_by_type(): void
    {
        AddyPrediction::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'monthly_revenue',
            'category' => 'money',
            'prediction_date' => now(),
            'target_date' => now()->addMonth(),
            'predicted_value' => 50000.00,
        ]);

        $salesPrediction = AddyPrediction::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'monthly_sales',
            'category' => 'sales',
            'prediction_date' => now(),
            'target_date' => now()->addMonth(),
            'predicted_value' => 100,
        ]);

        $result = AddyPrediction::getLatest($this->testOrganization->id, 'monthly_sales');

        $this->assertEquals($salesPrediction->id, $result->id);
    }

    /** @test */
    public function get_latest_filters_by_target_date(): void
    {
        $febPrediction = AddyPrediction::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'monthly_revenue',
            'category' => 'money',
            'prediction_date' => now(),
            'target_date' => '2025-02-28',
            'predicted_value' => 50000.00,
        ]);

        AddyPrediction::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'monthly_revenue',
            'category' => 'money',
            'prediction_date' => now(),
            'target_date' => '2025-03-31',
            'predicted_value' => 55000.00,
        ]);

        $result = AddyPrediction::getLatest(
            $this->testOrganization->id,
            'monthly_revenue',
            '2025-02-28'
        );

        $this->assertEquals($febPrediction->id, $result->id);
    }

    /** @test */
    public function get_latest_filters_by_organization(): void
    {
        $otherOrg = Organization::factory()->create();

        $testOrgPrediction = AddyPrediction::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'monthly_revenue',
            'category' => 'money',
            'prediction_date' => now(),
            'target_date' => now()->addMonth(),
            'predicted_value' => 50000.00,
        ]);

        AddyPrediction::create([
            'organization_id' => $otherOrg->id,
            'type' => 'monthly_revenue',
            'category' => 'money',
            'prediction_date' => now()->addDay(),
            'target_date' => now()->addMonth(),
            'predicted_value' => 75000.00,
        ]);

        $result = AddyPrediction::getLatest($this->testOrganization->id, 'monthly_revenue');

        $this->assertEquals($testOrgPrediction->id, $result->id);
    }

    /** @test */
    public function get_latest_returns_null_when_no_predictions(): void
    {
        $result = AddyPrediction::getLatest($this->testOrganization->id, 'monthly_revenue');

        $this->assertNull($result);
    }

    /** @test */
    public function calculate_accuracy_with_exact_prediction(): void
    {
        $prediction = AddyPrediction::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'monthly_revenue',
            'category' => 'money',
            'prediction_date' => now()->subMonth(),
            'target_date' => now(),
            'predicted_value' => 50000.00,
            'actual_value' => 50000.00,
        ]);

        $prediction->calculateAccuracy();
        $prediction->refresh();

        $this->assertEquals(1.00, (float) $prediction->accuracy);
    }

    /** @test */
    public function calculate_accuracy_with_10_percent_error(): void
    {
        $prediction = AddyPrediction::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'monthly_revenue',
            'category' => 'money',
            'prediction_date' => now()->subMonth(),
            'target_date' => now(),
            'predicted_value' => 50000.00,
            'actual_value' => 45000.00, // 10% under
        ]);

        $prediction->calculateAccuracy();
        $prediction->refresh();

        // Error = 5000, Percent Error = (5000/45000)*100 = 11.11%
        // Accuracy = (100 - 11.11) / 100 = 0.8889
        $this->assertEqualsWithDelta(0.89, (float) $prediction->accuracy, 0.01);
    }

    /** @test */
    public function calculate_accuracy_with_overprediction(): void
    {
        $prediction = AddyPrediction::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'monthly_revenue',
            'category' => 'money',
            'prediction_date' => now()->subMonth(),
            'target_date' => now(),
            'predicted_value' => 60000.00,
            'actual_value' => 50000.00,
        ]);

        $prediction->calculateAccuracy();
        $prediction->refresh();

        // Error = 10000, Percent Error = (10000/50000)*100 = 20%
        // Accuracy = (100 - 20) / 100 = 0.80
        $this->assertEquals(0.80, (float) $prediction->accuracy);
    }

    /** @test */
    public function calculate_accuracy_does_nothing_without_actual_value(): void
    {
        $prediction = AddyPrediction::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'monthly_revenue',
            'category' => 'money',
            'prediction_date' => now()->subMonth(),
            'target_date' => now(),
            'predicted_value' => 50000.00,
            'actual_value' => null,
        ]);

        $prediction->calculateAccuracy();
        $prediction->refresh();

        $this->assertNull($prediction->accuracy);
    }

    /** @test */
    public function calculate_accuracy_does_nothing_without_predicted_value(): void
    {
        $prediction = AddyPrediction::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'monthly_revenue',
            'category' => 'money',
            'prediction_date' => now()->subMonth(),
            'target_date' => now(),
            'predicted_value' => null,
            'actual_value' => 50000.00,
        ]);

        $prediction->calculateAccuracy();
        $prediction->refresh();

        $this->assertNull($prediction->accuracy);
    }

    /** @test */
    public function calculate_accuracy_handles_zero_actual_value(): void
    {
        $prediction = AddyPrediction::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'monthly_revenue',
            'category' => 'money',
            'prediction_date' => now()->subMonth(),
            'target_date' => now(),
            'predicted_value' => 50000.00,
            'actual_value' => 0.00,
        ]);

        $prediction->calculateAccuracy();
        $prediction->refresh();

        // With actual_value = 0, percent error = 100, accuracy = 0
        $this->assertEquals(0.00, (float) $prediction->accuracy);
    }

    /** @test */
    public function calculate_accuracy_minimum_is_zero(): void
    {
        $prediction = AddyPrediction::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'monthly_revenue',
            'category' => 'money',
            'prediction_date' => now()->subMonth(),
            'target_date' => now(),
            'predicted_value' => 100000.00, // Way over
            'actual_value' => 10000.00,
        ]);

        $prediction->calculateAccuracy();
        $prediction->refresh();

        // Error = 90000, Percent Error = 900%, max(0, 100-900)/100 = 0
        $this->assertEquals(0.00, (float) $prediction->accuracy);
    }

    /** @test */
    public function it_has_correct_fillable_attributes(): void
    {
        $prediction = new AddyPrediction();
        $fillable = $prediction->getFillable();

        $expected = [
            'organization_id',
            'type',
            'category',
            'prediction_date',
            'target_date',
            'predicted_value',
            'confidence',
            'factors',
            'metadata',
            'actual_value',
            'accuracy',
        ];

        foreach ($expected as $field) {
            $this->assertTrue(in_array($field, $fillable), "Field $field should be fillable");
        }
    }

    /** @test */
    public function it_stores_different_prediction_types(): void
    {
        $types = ['monthly_revenue', 'monthly_sales', 'cash_flow', 'inventory_demand'];

        foreach ($types as $type) {
            $prediction = AddyPrediction::create([
                'organization_id' => $this->testOrganization->id,
                'type' => $type,
                'category' => 'money',
                'prediction_date' => now(),
                'target_date' => now()->addMonth(),
                'predicted_value' => 50000.00,
            ]);

            $this->assertEquals($type, $prediction->type);

            $prediction->delete();
        }
    }

    /** @test */
    public function it_stores_different_categories(): void
    {
        $categories = ['money', 'sales', 'people', 'inventory'];

        foreach ($categories as $category) {
            $prediction = AddyPrediction::create([
                'organization_id' => $this->testOrganization->id,
                'type' => 'forecast',
                'category' => $category,
                'prediction_date' => now(),
                'target_date' => now()->addMonth(),
                'predicted_value' => 50000.00,
            ]);

            $this->assertEquals($category, $prediction->category);

            $prediction->delete();
        }
    }

    /** @test */
    public function it_stores_confidence_as_decimal(): void
    {
        $prediction = AddyPrediction::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'monthly_revenue',
            'category' => 'money',
            'prediction_date' => now(),
            'target_date' => now()->addMonth(),
            'predicted_value' => 50000.00,
            'confidence' => 0.87,
        ]);

        $this->assertEquals(0.87, (float) $prediction->confidence);
    }
}
