<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vendor>
 */
class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'vendor_code' => 'VEN' . str_pad(fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'type' => fake()->randomElement(['supplier', 'contractor', 'service_provider']),
            'name' => fake()->company(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'website' => fake()->optional()->url(),
            'tax_id' => fake()->optional()->numerify('##########'),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => fake()->country(),
            'postal_code' => fake()->postcode(),
            'payment_terms' => fake()->randomElement(['immediate', 'net_15', 'net_30', 'net_60']),
            'currency' => 'ZMW',
            'total_spent' => fake()->randomFloat(2, 0, 100000),
            'outstanding_balance' => fake()->randomFloat(2, 0, 10000),
            'status' => fake()->randomElement(['active', 'inactive']),
            'rating' => fake()->numberBetween(1, 5),
            'primary_contact_name' => fake()->name(),
            'primary_contact_email' => fake()->email(),
            'primary_contact_phone' => fake()->phoneNumber(),
            'notes' => fake()->optional()->paragraph(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }
}
