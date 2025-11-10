<?php

namespace Database\Factories;

use App\Models\TeamMember;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TeamMember>
 */
class TeamMemberFactory extends Factory
{
    protected $model = TeamMember::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'employee_number' => fake()->unique()->numerify('EMP-####'),
            'hire_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'job_title' => fake()->jobTitle(),
            'salary' => fake()->randomFloat(2, 20000, 200000),
            'employment_type' => fake()->randomElement(['full-time', 'part-time', 'contract', 'intern']),
            'is_active' => true,
        ];
    }
}
