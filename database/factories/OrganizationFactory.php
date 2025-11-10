<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        $name = fake()->company();
        
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'business_type' => fake()->randomElement(['sole_proprietorship', 'partnership', 'corporation', 'llc']),
            'industry' => fake()->randomElement(['retail', 'services', 'manufacturing', 'technology', 'healthcare']),
            'tone_preference' => fake()->randomElement(['formal', 'conversational', 'technical']),
            'currency' => fake()->randomElement(['USD', 'EUR', 'GBP', 'ZMW']),
            'timezone' => fake()->timezone(),
            'settings' => [],
        ];
    }
}
