<?php

namespace Database\Factories;

use App\Models\GoodsAndService;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GoodsAndService>
 */
class GoodsAndServiceFactory extends Factory
{
    protected $model = GoodsAndService::class;

    public function definition(): array
    {
        $costPrice = fake()->randomFloat(2, 10, 1000);
        $sellingPrice = $costPrice * fake()->randomFloat(2, 1.2, 3.0);
        
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(3, true),
            'type' => fake()->randomElement(['product', 'service']),
            'description' => fake()->optional()->sentence(),
            'sku' => fake()->unique()->bothify('SKU-####-???'),
            'barcode' => fake()->optional()->ean13(),
            'cost_price' => $costPrice,
            'selling_price' => $sellingPrice,
            'current_stock' => fake()->randomFloat(2, 0, 1000),
            'minimum_stock' => fake()->randomFloat(2, 10, 100),
            'unit' => fake()->randomElement(['piece', 'kg', 'liter', 'box', 'pack']),
            'category' => fake()->randomElement(['Electronics', 'Clothing', 'Food', 'Services', 'Office Supplies']),
            'is_active' => true,
            'track_stock' => fake()->boolean(70),
        ];
    }

    public function lowStock(): static
    {
        return $this->state(function (array $attributes) {
            $minimum = $attributes['minimum_stock'] ?? 10;
            return [
                'current_stock' => fake()->randomFloat(2, 0, $minimum),
                'track_stock' => true,
            ];
        });
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_stock' => 0,
            'track_stock' => true,
        ]);
    }
}
