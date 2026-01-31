<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quotation>
 */
class QuotationFactory extends Factory
{
    protected $model = Quotation::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 500, 50000);
        $taxAmount = $subtotal * 0.16;
        $total = $subtotal + $taxAmount;

        return [
            'organization_id' => Organization::factory(),
            'customer_id' => Customer::factory(),
            'created_by' => User::factory(),
            'quotation_number' => 'QUO-' . date('Y') . '-' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement(['draft', 'sent', 'viewed', 'accepted', 'rejected', 'expired']),
            'issue_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'valid_until' => fake()->dateTimeBetween('now', '+30 days'),
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'discount_percentage' => 0,
            'tax_amount' => $taxAmount,
            'tax_percentage' => 16,
            'total' => $total,
            'currency' => 'ZMW',
            'terms_and_conditions' => fake()->optional()->paragraph(),
            'payment_terms' => fake()->randomElement(['50% upfront, 50% on delivery', 'Net 30', 'On delivery']),
            'validity_days' => 30,
            'conversion_probability' => fake()->randomFloat(2, 0, 100),
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function viewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'viewed',
            'sent_at' => now()->subDays(2),
            'viewed_at' => now(),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
            'responded_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'responded_at' => now(),
            'rejection_reason' => fake()->sentence(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'valid_until' => fake()->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    public function validAndPending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => fake()->randomElement(['sent', 'viewed']),
            'valid_until' => fake()->dateTimeBetween('+1 day', '+30 days'),
        ]);
    }
}
