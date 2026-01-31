<?php

namespace Database\Factories;

use App\Models\License;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class LicenseFactory extends Factory
{
    protected $model = License::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => $this->faker->randomElement(['Business License', 'Operating Permit', 'Trade License', 'Health Permit']),
            'license_number' => 'LIC-' . $this->faker->unique()->numerify('######'),
            'issuing_authority' => $this->faker->company(),
            'issue_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'expiry_date' => $this->faker->dateTimeBetween('now', '+2 years'),
            'status' => $this->faker->randomElement(['active', 'expired', 'pending_renewal']),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
            'status' => 'expired',
        ]);
    }
}
