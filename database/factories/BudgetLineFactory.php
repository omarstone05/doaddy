<?php

namespace Database\Factories;

use App\Models\BudgetLine;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BudgetLine>
 */
class BudgetLineFactory extends Factory
{
    protected $model = BudgetLine::class;

    public function definition(): array
    {
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();
        
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->randomElement(['Marketing', 'Office Supplies', 'Rent', 'Utilities', 'Travel', 'Training']),
            'category' => fake()->randomElement(['Marketing', 'Office Supplies', 'Rent', 'Utilities', 'Travel', 'Training']),
            'amount' => fake()->randomFloat(2, 1000, 50000),
            'period' => fake()->randomElement(['monthly', 'quarterly', 'yearly']),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
