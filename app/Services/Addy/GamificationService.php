<?php

namespace App\Services\Addy;

use App\Models\GamificationXP;
use App\Models\GamificationBadge;
use App\Models\GamificationStreak;
use App\Models\Notification;
use App\Models\User;
use App\Modules\Retail\Models\Sale;
use App\Models\MoneyMovement;
use App\Models\Invoice;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GamificationService
{
    protected array $xpRewards;
    protected array $badges;
    protected int $xpPerLevel;

    public function __construct()
    {
        $this->xpRewards = config('gamification.xp_rewards', []);
        $this->badges = config('gamification.badges', []);
        $this->xpPerLevel = config('gamification.xp_per_level', 100);
    }

    /**
     * Award XP to a user for an action
     */
    public function awardXP(string $userId, string $reason, ?string $organizationId = null, ?array $context = []): int
    {
        if (!config('features.gamification', true)) {
            return 0;
        }

        $amount = $this->xpRewards[$reason] ?? 0;

        if ($amount <= 0) {
            return 0;
        }

        try {
            GamificationXP::create([
                'user_id' => $userId,
                'organization_id' => $organizationId,
                'xp_amount' => $amount,
                'reason' => $reason,
                'context' => $context,
            ]);

            // Create notification for XP earned
            $this->createXPNotification($userId, $organizationId, $amount, $reason);

            // Update streak
            $this->updateStreak($userId, $organizationId);

            // Check for badge unlocks
            $this->checkBadgeUnlocks($userId, $organizationId);

            return $amount;
        } catch (\Exception $e) {
            Log::error('Failed to award XP', [
                'user_id' => $userId,
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Update user's daily streak
     */
    public function updateStreak(string $userId, ?string $organizationId = null): void
    {
        $streak = GamificationStreak::firstOrNew([
            'user_id' => $userId,
            'streak_type' => 'daily',
        ]);

        if (!$streak->exists) {
            $streak->organization_id = $organizationId;
        }

        $today = Carbon::today();
        $lastActivity = $streak->last_activity_date ? Carbon::parse($streak->last_activity_date) : null;

        if (!$lastActivity) {
            // First activity ever
            $streak->current_streak = 1;
            $streak->longest_streak = 1;
        } elseif ($lastActivity->isSameDay($today)) {
            // Already counted today - no change needed
            return;
        } elseif ($lastActivity->isYesterday()) {
            // Consecutive day - increment streak
            $streak->current_streak++;
            $streak->longest_streak = max($streak->longest_streak, $streak->current_streak);

            // Award streak milestone bonuses
            $this->checkStreakMilestones($userId, $organizationId, $streak->current_streak);
        } elseif ($streak->weekend_pause_enabled && $this->wasWeekendGap($lastActivity, $today)) {
            // Weekend gap with pause enabled - continue streak
            $streak->current_streak++;
            $streak->longest_streak = max($streak->longest_streak, $streak->current_streak);
        } else {
            // Streak broken
            $streak->current_streak = 1;
        }

        $streak->last_activity_date = $today;
        $streak->save();
    }

    /**
     * Check if gap between dates was just a weekend
     */
    protected function wasWeekendGap(Carbon $lastActivity, Carbon $today): bool
    {
        $daysSince = $lastActivity->diffInDays($today);
        
        // If it's Monday and last activity was Friday, that's a weekend gap
        if ($daysSince === 3 && $today->isMonday() && $lastActivity->isFriday()) {
            return true;
        }
        
        // If it's Monday and last activity was Saturday/Sunday
        if ($daysSince <= 2 && $today->isMonday() && $lastActivity->isWeekend()) {
            return true;
        }

        return false;
    }

    /**
     * Check and award streak milestone bonuses
     */
    protected function checkStreakMilestones(string $userId, ?string $organizationId, int $currentStreak): void
    {
        $milestones = [
            7 => 'daily_streak_7',
            14 => 'daily_streak_14',
            30 => 'daily_streak_30',
            60 => 'daily_streak_60',
            90 => 'daily_streak_90',
            180 => 'daily_streak_180',
            365 => 'daily_streak_365',
        ];

        if (isset($milestones[$currentStreak])) {
            // Award streak bonus XP (but don't recurse into streak update)
            $amount = $this->xpRewards[$milestones[$currentStreak]] ?? 0;
            if ($amount > 0) {
                GamificationXP::create([
                    'user_id' => $userId,
                    'organization_id' => $organizationId,
                    'xp_amount' => $amount,
                    'reason' => $milestones[$currentStreak],
                    'context' => ['streak' => $currentStreak],
                ]);

                // Create notification for streak milestone
                $this->createStreakNotification($userId, $organizationId, $currentStreak, $amount);
            }
        }
    }

    /**
     * Create notification for streak milestone
     */
    protected function createStreakNotification(string $userId, ?string $organizationId, int $streakDays, int $bonusXP): void
    {
        try {
            $emoji = match(true) {
                $streakDays >= 365 => '🏆',
                $streakDays >= 180 => '👑',
                $streakDays >= 90 => '💎',
                $streakDays >= 60 => '🌟',
                $streakDays >= 30 => '⭐',
                $streakDays >= 14 => '✨',
                default => '🔥',
            };

            Notification::createForUser(
                $userId,
                $organizationId,
                'gamification_streak',
                "{$emoji} {$streakDays}-Day Streak!",
                "Amazing! You've maintained a {$streakDays}-day streak and earned {$bonusXP} bonus XP! Keep the momentum going!",
                '/dashboard'
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create streak notification', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Check if user qualifies for any new badges
     */
    public function checkBadgeUnlocks(string $userId, ?string $organizationId = null): void
    {
        foreach ($this->badges as $badgeType => $badgeConfig) {
            // Skip if already unlocked
            if ($this->hasBadge($userId, $badgeType)) {
                continue;
            }

            $currentValue = $this->getMetricValue($userId, $organizationId, $badgeConfig['metric']);

            if ($currentValue >= $badgeConfig['threshold']) {
                $this->awardBadge($userId, $organizationId, $badgeType, $badgeConfig);
            }
        }
    }

    /**
     * Get current value of a metric for badge calculation
     */
    protected function getMetricValue(string $userId, ?string $organizationId, string $metric): int
    {
        try {
            return match($metric) {
                'sales_count' => Sale::where('organization_id', $organizationId)->count(),
                'expenses_count' => MoneyMovement::where('organization_id', $organizationId)
                    ->where('flow_type', 'expense')
                    ->count(),
                'revenue_total' => (int) Sale::where('organization_id', $organizationId)->sum('total_amount'),
                'invoices_count' => Invoice::where('organization_id', $organizationId)->count(),
                'customers_count' => Customer::where('organization_id', $organizationId)->count(),
                'streak_days' => GamificationStreak::where('user_id', $userId)
                    ->where('streak_type', 'daily')
                    ->value('current_streak') ?? 0,
                default => 0,
            };
        } catch (\Exception $e) {
            Log::warning('Failed to get metric value', [
                'metric' => $metric,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Check if user already has a badge
     */
    protected function hasBadge(string $userId, string $badgeType): bool
    {
        return GamificationBadge::where('user_id', $userId)
            ->where('badge_type', $badgeType)
            ->exists();
    }

    /**
     * Award a badge to user
     */
    protected function awardBadge(string $userId, ?string $organizationId, string $badgeType, array $badgeConfig): void
    {
        try {
            GamificationBadge::create([
                'user_id' => $userId,
                'organization_id' => $organizationId,
                'badge_type' => $badgeType,
                'badge_name' => $badgeConfig['name'],
                'earned_at' => now(),
            ]);

            // Award bonus XP for unlocking badge
            $bonusXP = $this->xpRewards['badge_unlocked_bonus'] ?? 25;
            GamificationXP::create([
                'user_id' => $userId,
                'organization_id' => $organizationId,
                'xp_amount' => $bonusXP,
                'reason' => 'badge_unlocked_bonus',
                'context' => ['badge' => $badgeType],
            ]);

            // Create notification for badge unlocked
            $this->createBadgeNotification($userId, $organizationId, $badgeConfig);
        } catch (\Exception $e) {
            Log::error('Failed to award badge', [
                'user_id' => $userId,
                'badge_type' => $badgeType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create notification for XP earned
     */
    protected function createXPNotification(string $userId, ?string $organizationId, int $amount, string $reason): void
    {
        try {
            $reasonLabels = [
                'sale_recorded' => 'recording a sale',
                'sale_with_receipt' => 'recording a sale with receipt',
                'large_sale' => 'making a large sale',
                'expense_recorded' => 'recording an expense',
                'expense_with_receipt' => 'recording an expense with receipt',
                'customer_added' => 'adding a new customer',
                'invoice_issued' => 'issuing an invoice',
                'payment_recorded' => 'recording a payment',
                'product_added' => 'adding a new product',
                'stock_updated' => 'updating stock',
                'report_generated' => 'generating a report',
                'badge_unlocked_bonus' => 'unlocking a badge',
            ];

            $reasonText = $reasonLabels[$reason] ?? str_replace('_', ' ', $reason);

            Notification::createForUser(
                $userId,
                $organizationId,
                'gamification_xp',
                "You earned {$amount} XP! ⭐",
                "Great work! You earned {$amount} XP for {$reasonText}. Keep up the momentum!",
                '/dashboard'
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create XP notification', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Create notification for badge unlocked
     */
    protected function createBadgeNotification(string $userId, ?string $organizationId, array $badgeConfig): void
    {
        try {
            $icon = $badgeConfig['icon'] ?? '🏆';
            $name = $badgeConfig['name'] ?? 'Badge';
            $description = $badgeConfig['description'] ?? 'You unlocked a new badge!';

            Notification::createForUser(
                $userId,
                $organizationId,
                'gamification_badge',
                "{$icon} Badge Unlocked: {$name}!",
                "Congratulations! You've earned the \"{$name}\" badge. {$description}",
                '/dashboard'
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create badge notification', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get all gamification stats for a user
     */
    public function getUserStats(string $userId): array
    {
        $totalXP = GamificationXP::where('user_id', $userId)->sum('xp_amount');
        $level = floor($totalXP / $this->xpPerLevel) + 1;
        $xpInCurrentLevel = $totalXP % $this->xpPerLevel;
        $xpForNextLevel = $this->xpPerLevel - $xpInCurrentLevel;
        $progressPercentage = round(($xpInCurrentLevel / $this->xpPerLevel) * 100);

        $badges = GamificationBadge::where('user_id', $userId)
            ->orderBy('earned_at', 'desc')
            ->get()
            ->map(function ($badge) {
                $config = $this->badges[$badge->badge_type] ?? [];
                return [
                    'id' => $badge->id,
                    'type' => $badge->badge_type,
                    'name' => $badge->badge_name,
                    'icon' => $config['icon'] ?? '🏆',
                    'description' => $config['description'] ?? '',
                    'category' => $config['category'] ?? 'general',
                    'earned_at' => $badge->earned_at->toISOString(),
                ];
            });

        $streak = GamificationStreak::where('user_id', $userId)
            ->where('streak_type', 'daily')
            ->first();

        $levelTitle = $this->getLevelTitle($level);

        return [
            'total_xp' => (int) $totalXP,
            'level' => (int) $level,
            'level_title' => $levelTitle,
            'xp_in_current_level' => (int) $xpInCurrentLevel,
            'xp_for_next_level' => (int) $xpForNextLevel,
            'xp_progress_percentage' => (int) $progressPercentage,
            'badges' => $badges,
            'badges_count' => $badges->count(),
            'current_streak' => $streak?->current_streak ?? 0,
            'longest_streak' => $streak?->longest_streak ?? 0,
            'recent_badges' => $badges->take(3)->values(),
        ];
    }

    /**
     * Get level title based on level number
     */
    protected function getLevelTitle(int $level): string
    {
        $titles = config('gamification.level_titles', []);
        $title = 'Emerging Business';

        foreach ($titles as $minLevel => $levelTitle) {
            if ($level >= $minLevel) {
                $title = $levelTitle;
            }
        }

        return $title;
    }

    /**
     * Get all badges with unlock status for display
     */
    public function getAllBadgesWithStatus(string $userId): array
    {
        $earnedBadges = GamificationBadge::where('user_id', $userId)
            ->pluck('badge_type')
            ->toArray();

        return collect($this->badges)->map(function ($config, $type) use ($earnedBadges) {
            return [
                'type' => $type,
                'name' => $config['name'],
                'description' => $config['description'],
                'icon' => $config['icon'],
                'category' => $config['category'],
                'threshold' => $config['threshold'],
                'metric' => $config['metric'],
                'unlocked' => in_array($type, $earnedBadges),
            ];
        })->values()->toArray();
    }
}

