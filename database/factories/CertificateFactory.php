<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => $this->faker->randomElement(['ISO 9001', 'ISO 14001', 'ISO 27001', 'HACCP', 'CE Mark']),
            'certificate_number' => 'CERT-' . $this->faker->unique()->numerify('######'),
            'issuing_authority' => $this->faker->company(),
            'issue_date' => $this->faker->dateTimeBetween('-3 years', 'now'),
            'expiry_date' => $this->faker->dateTimeBetween('now', '+3 years'),
            'status' => $this->faker->randomElement(['active', 'expired', 'pending']),
        ];
    }
}
