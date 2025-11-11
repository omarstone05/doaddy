<?php

namespace App\Services\Admin;

use App\Models\Organization;
use App\Models\User;
use App\Models\SupportTicket;
use App\Models\AddyInsight;
use App\Models\AddyChatMessage;
use App\Models\SystemMetric;
use Illuminate\Support\Facades\DB;

class AdminAnalyticsService
{
    /**
     * Get dashboard overview statistics
     */
    public function getDashboardStats(): array
    {
        return [
            'organizations' => [
                'total' => Organization::count(),
                'active' => Organization::where('status', 'active')->count(),
                'trial' => Organization::where('status', 'trial')->count(),
                'suspended' => Organization::where('status', 'suspended')->count(),
                'new_this_month' => Organization::whereMonth('created_at', now()->month)->count(),
            ],
            'users' => [
                'total' => User::count(),
                'new_this_month' => User::whereMonth('created_at', now()->month)->count(),
                'active_today' => User::whereDate('last_active_at', today())->count(),
            ],
            'platform' => [
                'total_organizations' => Organization::count(),
                'active_organizations' => Organization::where('status', 'active')->count(),
                'trial_organizations' => Organization::where('status', 'trial')->count(),
                'suspended_organizations' => Organization::where('status', 'suspended')->count(),
            ],
            'support' => [
                'open_tickets' => SupportTicket::where('status', 'open')->count(),
                'urgent_tickets' => SupportTicket::where('priority', 'urgent')->where('status', '!=', 'closed')->count(),
                'unassigned' => SupportTicket::whereNull('assigned_to')->where('status', 'open')->count(),
                'avg_response_time' => $this->getAverageResponseTime(),
                'avg_resolution_time' => $this->getAverageResolutionTime(),
            ],
            'addy' => [
                'insights_generated_today' => AddyInsight::whereDate('created_at', today())->count(),
                'chat_messages_today' => AddyChatMessage::whereDate('created_at', today())->count(),
                'active_conversations' => AddyChatMessage::distinct('organization_id')->whereDate('created_at', today())->count('organization_id'),
            ],
            'revenue' => [
                'mrr' => Organization::sum('mrr'),
                'arr' => Organization::sum('mrr') * 12,
                'avg_per_org' => Organization::where('mrr', '>', 0)->avg('mrr') ?? 0,
            ],
        ];
    }

    /**
     * Get organization growth chart data
     */
    public function getOrganizationGrowth(int $days = 30): array
    {
        $data = Organization::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'))->toArray(),
            'values' => $data->pluck('count')->toArray(),
        ];
    }

    /**
     * Get user activity chart data
     */
    public function getUserActivity(int $days = 30): array
    {
        $data = User::selectRaw('DATE(last_active_at) as date, COUNT(*) as count')
            ->whereNotNull('last_active_at')
            ->where('last_active_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'))->toArray(),
            'values' => $data->pluck('count')->toArray(),
        ];
    }

    /**
     * Get ticket volume chart data
     */
    public function getTicketVolume(int $days = 30): array
    {
        $created = SupportTicket::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $resolved = SupportTicket::selectRaw('DATE(resolved_at) as date, COUNT(*) as count')
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $dates = $created->pluck('date')->merge($resolved->pluck('date'))->unique()->sort()->values();

        return [
            'labels' => $dates->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'))->toArray(),
            'created' => $dates->map(fn($d) => $created->where('date', $d)->first()?->count ?? 0)->toArray(),
            'resolved' => $dates->map(fn($d) => $resolved->where('date', $d)->first()?->count ?? 0)->toArray(),
        ];
    }

    protected function getAverageResponseTime(): ?float
    {
        $avg = SupportTicket::whereNotNull('first_response_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, first_response_at)) as avg_hours')
            ->value('avg_hours');

        return $avg ? round($avg, 1) : null;
    }

    protected function getAverageResolutionTime(): ?float
    {
        $avg = SupportTicket::whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
            ->value('avg_hours');

        return $avg ? round($avg, 1) : null;
    }

    /**
     * Get revenue chart data
     */
    public function getRevenueChart(int $months = 12): array
    {
        // This would integrate with billing system
        // For now, return MRR history
        return [
            'labels' => collect(range($months - 1, 0))->map(fn($i) => now()->subMonths($i)->format('M Y'))->toArray(),
            'values' => collect(range($months - 1, 0))->map(fn($i) => Organization::where('created_at', '<=', now()->subMonths($i))->sum('mrr'))->toArray(),
        ];
    }

    /**
     * Get system health metrics
     */
    public function getSystemHealth(): array
    {
        return [
            'database' => $this->checkDatabaseHealth(),
            'redis' => $this->checkRedisHealth(),
            'queue' => $this->checkQueueHealth(),
            'storage' => $this->checkStorageHealth(),
        ];
    }


    protected function checkDatabaseHealth(): array
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'healthy', 'message' => 'Connected'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    protected function checkRedisHealth(): array
    {
        try {
            if (config('cache.default') === 'redis') {
                \Cache::store('redis')->put('health_check', true, 10);
                return ['status' => 'healthy', 'message' => 'Connected'];
            }
            return ['status' => 'skipped', 'message' => 'Redis not configured'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    protected function checkQueueHealth(): array
    {
        // Check failed jobs
        try {
            $failedJobs = DB::table('failed_jobs')->count();
            
            if ($failedJobs > 10) {
                return ['status' => 'warning', 'message' => "{$failedJobs} failed jobs"];
            }

            return ['status' => 'healthy', 'message' => 'Running'];
        } catch (\Exception $e) {
            return ['status' => 'skipped', 'message' => 'Queue table not found'];
        }
    }

    protected function checkStorageHealth(): array
    {
        try {
            $diskSpace = disk_free_space('/');
            $diskTotal = disk_total_space('/');
            $percentFree = ($diskSpace / $diskTotal) * 100;

            if ($percentFree < 10) {
                return ['status' => 'warning', 'message' => 'Low disk space'];
            }

            return ['status' => 'healthy', 'message' => round($percentFree, 1) . '% free'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}

