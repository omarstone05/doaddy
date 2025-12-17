<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'customer_code' => 'CUS-' . Str::uuid(),
            'type' => 'business',
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'billing_address' => fake()->optional()->address(),
            'shipping_address' => fake()->optional()->address(),
            'tax_id' => fake()->optional()->numerify('TAX-####'),
        ];
    }
}
