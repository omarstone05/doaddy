<?php

namespace Database\Factories;

use App\Models\MoneyMovement;
use App\Models\Organization;
use App\Models\MoneyAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MoneyMovement>
 */
class MoneyMovementFactory extends Factory
{
    protected $model = MoneyMovement::class;

    public function definition(): array
    {
        $flowType = fake()->randomElement(['income', 'expense', 'transfer']);
        
        return [
            'organization_id' => Organization::factory(),
            'flow_type' => $flowType,
            'amount' => fake()->randomFloat(2, 10, 10000),
            'currency' => fake()->randomElement(['USD', 'EUR', 'GBP', 'ZMW']),
            'transaction_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'from_account_id' => $flowType === 'expense' || $flowType === 'transfer' ? MoneyAccount::factory() : null,
            'to_account_id' => $flowType === 'income' || $flowType === 'transfer' ? MoneyAccount::factory() : null,
            'description' => fake()->sentence(),
            'category' => fake()->randomElement(['Marketing', 'Office Supplies', 'Rent', 'Utilities', 'Salary', 'Sales', 'Services']),
            'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'created_by_id' => User::factory(),
        ];
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'flow_type' => 'expense',
            'from_account_id' => MoneyAccount::factory(),
            'to_account_id' => null,
        ]);
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'flow_type' => 'income',
            'from_account_id' => null,
            'to_account_id' => MoneyAccount::factory(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }
}
