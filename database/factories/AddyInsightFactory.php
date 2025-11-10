<?php

namespace Database\Factories;

use App\Models\AddyInsight;
use App\Models\Organization;
use App\Models\AddyState;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AddyInsight>
 */
class AddyInsightFactory extends Factory
{
    protected $model = AddyInsight::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'addy_state_id' => AddyState::factory(),
            'type' => fake()->randomElement(['alert', 'suggestion', 'achievement', 'info']),
            'category' => fake()->randomElement(['money', 'sales', 'people', 'inventory', 'cross-section']),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomFloat(2, 0.1, 1.0),
            'is_actionable' => fake()->boolean(70),
            'suggested_actions' => [],
            'action_url' => fake()->optional()->url(),
            'status' => 'active',
            'dismissed_at' => null,
            'completed_at' => null,
            'expires_at' => fake()->optional()->dateTimeBetween('now', '+30 days'),
        ];
    }
}
