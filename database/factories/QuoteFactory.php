<?php

namespace Database\Factories;

use App\Models\Quote;
use App\Models\Organization;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quote>
 */
class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 10000);
        $taxAmount = $subtotal * 0.16;
        $total = $subtotal + $taxAmount;
        
        return [
            'organization_id' => Organization::factory(),
            'customer_id' => Customer::factory(),
            'quote_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'expiry_date' => fake()->dateTimeBetween('now', '+30 days'),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => 0,
            'total_amount' => $total,
            'status' => fake()->randomElement(['draft', 'sent', 'accepted', 'rejected', 'expired']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
