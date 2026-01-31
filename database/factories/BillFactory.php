<?php

namespace Database\Factories;

use App\Models\Bill;
use App\Models\Organization;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bill>
 */
class BillFactory extends Factory
{
    protected $model = Bill::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 10000);
        $taxAmount = $subtotal * 0.16;
        $total = $subtotal + $taxAmount;

        return [
            'organization_id' => Organization::factory(),
            'vendor_id' => Vendor::factory(),
            'created_by' => User::factory(),
            'bill_number' => 'BILL-' . date('Y') . '-' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'vendor_invoice_number' => fake()->optional()->numerify('INV-####'),
            'status' => fake()->randomElement(['draft', 'pending', 'approved', 'paid', 'cancelled']),
            'payment_status' => fake()->randomElement(['unpaid', 'partially_paid', 'paid']),
            'bill_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'due_date' => fake()->dateTimeBetween('now', '+60 days'),
            'amount' => $total,
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'amount_paid' => 0,
            'amount_due' => $total,
            'currency' => 'ZMW',
            'category' => fake()->randomElement(['Office Supplies', 'Rent', 'Utilities', 'Marketing', 'Services']),
            'description' => fake()->optional()->sentence(),
            'notes' => fake()->optional()->paragraph(),
        ];
    }

    public function unpaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'unpaid',
            'status' => 'approved',
            'amount_paid' => 0,
            'amount_due' => $attributes['total'] ?? 1000,
        ]);
    }

    public function partiallyPaid(): static
    {
        return $this->state(function (array $attributes) {
            $total = $attributes['total'] ?? 1000;
            $paid = $total * 0.5;
            return [
                'payment_status' => 'partially_paid',
                'status' => 'approved',
                'amount_paid' => $paid,
                'amount_due' => $total - $paid,
            ];
        });
    }

    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            $total = $attributes['total'] ?? 1000;
            return [
                'payment_status' => 'paid',
                'status' => 'paid',
                'amount_paid' => $total,
                'amount_due' => 0,
            ];
        });
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'unpaid',
            'status' => 'overdue',
            'due_date' => fake()->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    public function dueSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'unpaid',
            'status' => 'approved',
            'due_date' => fake()->dateTimeBetween('now', '+7 days'),
        ]);
    }
}
