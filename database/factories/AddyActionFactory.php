<?php

namespace Database\Factories;

use App\Models\AddyAction;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AddyAction>
 */
class AddyActionFactory extends Factory
{
    protected $model = AddyAction::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'action_type' => fake()->randomElement(['create_transaction', 'send_invoice_reminders', 'adjust_budget', 'create_invoice']),
            'category' => fake()->randomElement(['money', 'sales', 'people', 'inventory']),
            'status' => fake()->randomElement(['pending', 'confirmed', 'executed', 'cancelled', 'failed']),
            'parameters' => [],
            'preview_data' => [],
            'result' => null,
            'confirmed_at' => null,
            'executed_at' => null,
            'error_message' => null,
            'was_successful' => null,
            'user_rating' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function executed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'executed',
            'executed_at' => now(),
            'was_successful' => true,
        ]);
    }
}
