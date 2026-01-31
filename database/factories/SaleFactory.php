<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\User;
use App\Modules\Retail\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Retail\Models\Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 5000);
        $taxAmount = $subtotal * 0.16;
        $discountAmount = fake()->randomFloat(2, 0, $subtotal * 0.1);
        $totalAmount = $subtotal + $taxAmount - $discountAmount;

        return [
            'organization_id' => Organization::factory(),
            'sale_number' => 'SALE-' . date('Y') . '-' . str_pad(fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'transaction_type' => fake()->randomElement(['sale', 'return']),
            'status' => fake()->randomElement(['completed', 'voided', 'returned']),
            'sale_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'sale_time' => fake()->time(),
            'customer_id' => Customer::factory(),
            'customer_name' => fake()->company(),
            'customer_phone' => fake()->phoneNumber(),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'amount_paid' => $totalAmount,
            'change_given' => 0,
            'currency' => 'ZMW',
            'payment_method' => fake()->randomElement(['cash', 'mobile_money', 'card', 'credit']),
            'cashier_id' => User::factory(),
            'total_cost' => $subtotal * 0.6,
            'total_profit' => $totalAmount - ($subtotal * 0.6),
            'profit_margin' => fake()->randomFloat(2, 10, 40),
            'notes' => fake()->optional()->sentence(),
            'receipt_printed' => fake()->boolean(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    public function voided(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'voided',
        ]);
    }

    public function thisMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'sale_date' => fake()->dateTimeBetween('first day of this month', 'now'),
        ]);
    }

    public function thisYear(): static
    {
        return $this->state(fn (array $attributes) => [
            'sale_date' => fake()->dateTimeBetween('first day of january this year', 'now'),
        ]);
    }
}
