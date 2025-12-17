<?php

namespace Database\Factories;

use App\Models\AddyState;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AddyState>
 */
class AddyStateFactory extends Factory
{
    protected $model = AddyState::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'focus_area' => fake()->randomElement(['Money', 'Sales', 'People', 'Inventory', 'Overview']),
            'urgency' => fake()->randomFloat(2, 0.1, 1.0),
            'context' => fake()->sentence(),
            'mood' => fake()->randomElement(['neutral', 'concerned', 'optimistic', 'alert']),
            'perception_data' => [],
            'priorities' => [],
            'last_thought_cycle' => now(),
        ];
    }
}
