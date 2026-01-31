<?php

namespace Database\Factories;

use App\Models\LeaveType;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => $this->faker->randomElement(['Annual Leave', 'Sick Leave', 'Maternity Leave', 'Paternity Leave', 'Compassionate Leave']),
            'description' => $this->faker->sentence(),
            'maximum_days_per_year' => $this->faker->numberBetween(5, 30),
            'can_carry_forward' => $this->faker->boolean(50),
            'max_carry_forward_days' => $this->faker->numberBetween(0, 10),
            'is_active' => true,
        ];
    }
}
