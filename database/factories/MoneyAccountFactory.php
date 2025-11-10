<?php

namespace Database\Factories;

use App\Models\MoneyAccount;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MoneyAccount>
 */
class MoneyAccountFactory extends Factory
{
    protected $model = MoneyAccount::class;

    public function definition(): array
    {
        $balance = fake()->randomFloat(2, 1000, 100000);
        
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->randomElement(['Main Account', 'Business Account', 'Petty Cash', 'Savings Account']),
            'type' => fake()->randomElement(['cash', 'bank', 'credit', 'investment']),
            'account_number' => fake()->numerify('####-####-####'),
            'bank_name' => fake()->optional()->company(),
            'currency' => fake()->randomElement(['USD', 'EUR', 'GBP', 'ZMW']),
            'opening_balance' => $balance,
            'current_balance' => $balance,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
