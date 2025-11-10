<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 10000);
        $taxAmount = $subtotal * 0.16; // 16% tax
        $total = $subtotal + $taxAmount;
        
        return [
            'organization_id' => Organization::factory(),
            'customer_id' => Customer::factory(),
            'invoice_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'due_date' => fake()->dateTimeBetween('now', '+30 days'),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => 0,
            'total_amount' => $total,
            'paid_amount' => 0,
            'status' => fake()->randomElement(['draft', 'sent', 'paid', 'overdue', 'cancelled']),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'paid',
                'paid_amount' => $attributes['total_amount'],
            ];
        });
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'due_date' => fake()->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }
}
