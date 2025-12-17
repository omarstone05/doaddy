<?php

namespace Database\Factories;

use App\Models\StockMovement;
use App\Models\Organization;
use App\Models\GoodsAndService;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'goods_service_id' => GoodsAndService::factory(),
            'movement_type' => fake()->randomElement(['in', 'out', 'adjustment']),
            'quantity' => fake()->randomFloat(2, 1, 100),
            'reference_number' => fake()->optional()->numerify('REF-####'),
            'notes' => fake()->optional()->sentence(),
            'created_by_id' => User::factory(),
        ];
    }
}
