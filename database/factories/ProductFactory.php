<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => $this->faker->words(3, true),
            'sku' => 'SKU-' . $this->faker->unique()->numerify('######'),
            'description' => $this->faker->sentence(),
            'unit_price' => $this->faker->randomFloat(2, 10, 1000),
            'cost_price' => $this->faker->randomFloat(2, 5, 500),
            'quantity' => $this->faker->numberBetween(0, 1000),
            'reorder_level' => $this->faker->numberBetween(5, 50),
            'unit_of_measure' => $this->faker->randomElement(['pieces', 'kg', 'liters', 'meters']),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 5,
            'reorder_level' => 10,
        ]);
    }
}
