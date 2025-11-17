<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserMetric;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UserMetricsService
{
    /**
     * Track a metric for a user
     */
    public function track(User $user, string $metricType, int $value = 1, array $metadata = []): UserMetric
    {
        $date = Carbon::today();

        $metric = UserMetric::firstOrNew([
            'user_id' => $user->id,
            'date' => $date,
            'metric_type' => $metricType,
        ]);

        if ($metric->exists) {
            $metric->increment('value', $value);
        } else {
            $metric->value = $value;
            $metric->metadata = $metadata ?: null;
            $metric->save();
        }

        // Update metadata if provided and different
        if ($metadata && $metric->metadata !== $metadata) {
            $metric->update(['metadata' => $metadata]);
        }

        return $metric->fresh();
    }

    /**
     * Track a login event
     */
    public function trackLogin(User $user): UserMetric
    {
        return $this->track($user, 'login', 1, [
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Track session duration
     */
    public function trackSessionDuration(User $user, int $seconds): UserMetric
    {
        return $this->track($user, 'session_duration', $seconds);
    }

    /**
     * Track page view
     */
    public function trackPageView(User $user, string $page, array $metadata = []): UserMetric
    {
        return $this->track($user, 'page_view', 1, array_merge([
            'page' => $page,
        ], $metadata));
    }

    /**
     * Track feature usage
     */
    public function trackFeatureUsage(User $user, string $feature, array $metadata = []): UserMetric
    {
        return $this->track($user, 'feature_usage', 1, array_merge([
            'feature' => $feature,
        ], $metadata));
    }

    /**
     * Track action (generic action tracking)
     */
    public function trackAction(User $user, string $action, array $metadata = []): UserMetric
    {
        return $this->track($user, 'action', 1, array_merge([
            'action' => $action,
        ], $metadata));
    }

    /**
     * Get metrics for a user
     */
    public function getUserMetrics(User $user, ?string $metricType = null, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = UserMetric::where('user_id', $user->id);

        if ($metricType) {
            $query->where('metric_type', $metricType);
        }

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        return $query->orderBy('date', 'desc')->get()->toArray();
    }

    /**
     * Get aggregated metrics for a user
     */
    public function getAggregatedMetrics(User $user, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->subDays(30);
        $endDate = $endDate ?? Carbon::now();

        $metrics = UserMetric::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->select('metric_type', DB::raw('SUM(value) as total'), DB::raw('AVG(value) as average'))
            ->groupBy('metric_type')
            ->get()
            ->keyBy('metric_type')
            ->map(function ($metric) {
                return [
                    'total' => (int) $metric->total,
                    'average' => round((float) $metric->average, 2),
                ];
            })
            ->toArray();

        return $metrics;
    }

    /**
     * Get daily metrics for a user
     */
    public function getDailyMetrics(User $user, string $metricType, int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);

        $metrics = UserMetric::where('user_id', $user->id)
            ->where('metric_type', $metricType)
            ->where('date', '>=', $startDate)
            ->select('date', DB::raw('SUM(value) as total'))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return [
            'labels' => $metrics->pluck('date')->map(fn($d) => $d->format('M d'))->toArray(),
            'values' => $metrics->pluck('total')->toArray(),
        ];
    }

    /**
     * Get user statistics summary
     */
    public function getUserStats(User $user): array
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $sevenDaysAgo = Carbon::now()->subDays(7);
        $today = Carbon::today();

        return [
            'total_logins' => UserMetric::where('user_id', $user->id)
                ->where('metric_type', 'login')
                ->sum('value'),
            'logins_last_30_days' => UserMetric::where('user_id', $user->id)
                ->where('metric_type', 'login')
                ->where('date', '>=', $thirtyDaysAgo)
                ->sum('value'),
            'logins_last_7_days' => UserMetric::where('user_id', $user->id)
                ->where('metric_type', 'login')
                ->where('date', '>=', $sevenDaysAgo)
                ->sum('value'),
            'logins_today' => UserMetric::where('user_id', $user->id)
                ->where('metric_type', 'login')
                ->where('date', $today)
                ->sum('value'),
            'total_session_duration' => UserMetric::where('user_id', $user->id)
                ->where('metric_type', 'session_duration')
                ->sum('value'),
            'avg_session_duration' => UserMetric::where('user_id', $user->id)
                ->where('metric_type', 'session_duration')
                ->where('date', '>=', $thirtyDaysAgo)
                ->avg('value') ?? 0,
            'total_page_views' => UserMetric::where('user_id', $user->id)
                ->where('metric_type', 'page_view')
                ->where('date', '>=', $thirtyDaysAgo)
                ->sum('value'),
            'total_actions' => UserMetric::where('user_id', $user->id)
                ->where('metric_type', 'action')
                ->where('date', '>=', $thirtyDaysAgo)
                ->sum('value'),
            'last_active_date' => UserMetric::where('user_id', $user->id)
                ->latest('date')
                ->value('date'),
            'most_used_features' => $this->getMostUsedFeatures($user, $thirtyDaysAgo),
        ];
    }

    /**
     * Get most used features for a user
     */
    protected function getMostUsedFeatures(User $user, Carbon $since): array
    {
        $features = UserMetric::where('user_id', $user->id)
            ->where('metric_type', 'feature_usage')
            ->where('date', '>=', $since)
            ->get()
            ->groupBy(function ($metric) {
                return $metric->metadata['feature'] ?? 'unknown';
            })
            ->map(function ($group) {
                return $group->sum('value');
            })
            ->sortDesc()
            ->take(10)
            ->toArray();

        return $features;
    }

    /**
     * Get activity timeline for a user
     */
    public function getActivityTimeline(User $user, int $days = 7): array
    {
        $startDate = Carbon::now()->subDays($days);

        $metrics = UserMetric::where('user_id', $user->id)
            ->where('date', '>=', $startDate)
            ->select('date', 'metric_type', DB::raw('SUM(value) as total'))
            ->groupBy('date', 'metric_type')
            ->orderBy('date', 'desc')
            ->orderBy('metric_type')
            ->get()
            ->groupBy('date')
            ->map(function ($dayMetrics) {
                return $dayMetrics->keyBy('metric_type')->map(function ($metric) {
                    return (int) $metric->total;
                })->toArray();
            })
            ->toArray();

        return $metrics;
    }
}

