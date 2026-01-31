<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        $purchasePrice = $this->faker->randomFloat(2, 500, 50000);
        
        return [
            'organization_id' => Organization::factory(),
            'name' => $this->faker->randomElement(['Laptop', 'Desktop', 'Printer', 'Vehicle', 'Furniture', 'Server']),
            'asset_number' => 'AST-' . $this->faker->unique()->numerify('######'),
            'category' => $this->faker->randomElement(['equipment', 'furniture', 'vehicle', 'electronics', 'other']),
            'description' => $this->faker->sentence(),
            'purchase_date' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'purchase_price' => $purchasePrice,
            'current_value' => $purchasePrice * $this->faker->randomFloat(2, 0.3, 0.9),
            'status' => $this->faker->randomElement(['in_use', 'maintenance', 'retired', 'disposed']),
            'location' => $this->faker->city(),
        ];
    }

    public function inUse(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_use',
        ]);
    }

    public function retired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'retired',
        ]);
    }
}
