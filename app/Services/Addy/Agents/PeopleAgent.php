<?php

namespace App\Services\Addy\Agents;

use App\Models\Organization;
use App\Models\User;
use App\Models\PayrollRun;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\DB;

class PeopleAgent
{
    protected Organization $organization;

    public function __construct(Organization $organization)
    {
        $this->organization = $organization;
    }

    public function perceive(): array
    {
        return [
            'team_stats' => $this->getTeamStats(),
            'payroll_health' => $this->getPayrollHealth(),
            'leave_patterns' => $this->getLeavePatterns(),
            'attendance_trends' => $this->getAttendanceTrends(),
        ];
    }

    protected function getTeamStats(): array
    {
        $allTeam = User::where('organization_id', $this->organization->id)->get();
        
        $activeTeam = $allTeam->where('status', 'active');
        $onLeave = $allTeam->where('on_leave', true);

        return [
            'total' => $allTeam->count(),
            'active' => $activeTeam->count(),
            'on_leave' => $onLeave->count(),
            'new_this_month' => $allTeam->filter(function($user) {
                return $user->created_at >= now()->startOfMonth();
            })->count(),
        ];
    }

    protected function getPayrollHealth(): array
    {
        $thisMonth = PayrollRun::where('organization_id', $this->organization->id)
            ->whereBetween('period_start', [now()->startOfMonth(), now()->endOfMonth()])
            ->first();

        $lastMonth = PayrollRun::where('organization_id', $this->organization->id)
            ->whereBetween('period_start', [
                now()->subMonth()->startOfMonth(), 
                now()->subMonth()->endOfMonth()
            ])
            ->first();

        $nextPayroll = PayrollRun::where('organization_id', $this->organization->id)
            ->where('status', 'pending')
            ->where('payment_date', '>', now())
            ->orderBy('payment_date')
            ->first();

        return [
            'this_month_total' => $thisMonth ? $thisMonth->total_amount : 0,
            'last_month_total' => $lastMonth ? $lastMonth->total_amount : 0,
            'next_payroll_date' => $nextPayroll ? $nextPayroll->payment_date : null,
            'next_payroll_amount' => $nextPayroll ? $nextPayroll->total_amount : 0,
            'days_until_payroll' => $nextPayroll ? now()->diffInDays($nextPayroll->payment_date) : null,
        ];
    }

    protected function getLeavePatterns(): array
    {
        $thisMonth = LeaveRequest::where('organization_id', $this->organization->id)
            ->whereBetween('start_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->get();

        $upcoming = LeaveRequest::where('organization_id', $this->organization->id)
            ->where('status', 'approved')
            ->where('start_date', '>', now())
            ->where('start_date', '<=', now()->addWeeks(2))
            ->get();

        $pending = LeaveRequest::where('organization_id', $this->organization->id)
            ->where('status', 'pending')
            ->get();

        return [
            'this_month_count' => $thisMonth->count(),
            'upcoming_count' => $upcoming->count(),
            'pending_requests' => $pending->count(),
            'total_days_this_month' => $thisMonth->sum('number_of_days'),
        ];
    }

    protected function getAttendanceTrends(): array
    {
        // This is a placeholder - implement based on your attendance tracking
        return [
            'avg_attendance_rate' => 95, // percentage
            'late_arrivals_this_week' => 0,
            'early_departures_this_week' => 0,
        ];
    }

    public function analyze(): array
    {
        $perception = $this->perceive();
        $insights = [];

        // Upcoming payroll alert
        if ($perception['payroll_health']['days_until_payroll'] !== null && 
            $perception['payroll_health']['days_until_payroll'] <= 7) {
            $insights[] = [
                'type' => 'alert',
                'category' => 'people',
                'title' => 'Payroll Due Soon',
                'description' => "Payroll of " . number_format($perception['payroll_health']['next_payroll_amount'], 2) . 
                    " is due in {$perception['payroll_health']['days_until_payroll']} days.",
                'priority' => 0.85,
                'is_actionable' => true,
                'suggested_actions' => [
                    'Review payroll items',
                    'Ensure sufficient funds',
                    'Approve payroll run',
                ],
                'action_url' => '/payroll/runs',
            ];
        }

        // Pending leave requests
        if ($perception['leave_patterns']['pending_requests'] > 0) {
            $insights[] = [
                'type' => 'suggestion',
                'category' => 'people',
                'title' => 'Pending Leave Requests',
                'description' => "{$perception['leave_patterns']['pending_requests']} leave request(s) waiting for approval.",
                'priority' => 0.6,
                'is_actionable' => true,
                'suggested_actions' => [
                    'Review leave requests',
                    'Approve or decline pending requests',
                    'Check team coverage',
                ],
                'action_url' => '/leave/requests',
            ];
        }

        // High leave volume
        if ($perception['leave_patterns']['upcoming_count'] > 5) {
            $insights[] = [
                'type' => 'observation',
                'category' => 'people',
                'title' => 'High Leave Volume Ahead',
                'description' => "{$perception['leave_patterns']['upcoming_count']} team members have approved leave in the next 2 weeks.",
                'priority' => 0.65,
                'is_actionable' => true,
                'suggested_actions' => [
                    'Plan for reduced capacity',
                    'Assign backup responsibilities',
                    'Adjust project timelines if needed',
                ],
                'action_url' => '/leave/requests',
            ];
        }

        // Payroll cost increase
        if ($perception['payroll_health']['last_month_total'] > 0) {
            $change = (($perception['payroll_health']['this_month_total'] - 
                       $perception['payroll_health']['last_month_total']) / 
                       $perception['payroll_health']['last_month_total']) * 100;

            if ($change > 10) {
                $insights[] = [
                    'type' => 'observation',
                    'category' => 'people',
                    'title' => 'Payroll Cost Increase',
                    'description' => "Payroll costs increased by " . round($change, 1) . "% from last month.",
                    'priority' => 0.55,
                    'is_actionable' => true,
                    'suggested_actions' => [
                        'Review new hires',
                        'Check for overtime costs',
                        'Verify payroll calculations',
                    ],
                    'action_url' => '/payroll/runs',
                ];
            }
        }

        // Team growth
        if ($perception['team_stats']['new_this_month'] > 0) {
            $insights[] = [
                'type' => 'achievement',
                'category' => 'people',
                'title' => 'Team Expansion',
                'description' => "Welcome to {$perception['team_stats']['new_this_month']} new team member(s) this month!",
                'priority' => 0.4,
                'is_actionable' => false,
                'suggested_actions' => [
                    'Complete onboarding process',
                    'Assign mentors',
                    'Schedule check-ins',
                ],
                'action_url' => '/team',
            ];
        }

        return $insights;
    }
}

