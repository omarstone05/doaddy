<?php

namespace Database\Factories;

use App\Models\LeaveRequest;
use App\Models\Organization;
use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+30 days');
        // Ensure end_date is always after start_date
        $endDate = fake()->dateTimeBetween($startDate->format('Y-m-d H:i:s'), $startDate->format('Y-m-d H:i:s') . ' +14 days');
        $days = max(1, (int) $startDate->diff($endDate)->days);
        
        return [
            'organization_id' => Organization::factory(),
            'team_member_id' => TeamMember::factory(),
            'leave_type_id' => null, // Can be set if LeaveType model exists
            'start_date' => $startDate,
            'end_date' => $endDate,
            'number_of_days' => $days,
            'reason' => fake()->sentence(),
            'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'approved_by_id' => null,
            'approved_at' => null,
            'comments' => fake()->optional()->sentence(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }
}
