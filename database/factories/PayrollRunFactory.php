<?php

namespace Database\Factories;

use App\Models\PayrollRun;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PayrollRun>
 */
class PayrollRunFactory extends Factory
{
    protected $model = PayrollRun::class;

    public function definition(): array
    {
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();
        
        return [
            'organization_id' => Organization::factory(),
            'pay_period' => fake()->randomElement(['monthly', 'bi-weekly', 'weekly']),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => fake()->randomElement(['draft', 'pending', 'processed', 'paid']),
            'total_amount' => fake()->randomFloat(2, 10000, 100000),
            'created_by_id' => User::factory(),
            'processed_at' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'payment_date' => fake()->dateTimeBetween('now', '+7 days'),
            ];
        });
    }
}
