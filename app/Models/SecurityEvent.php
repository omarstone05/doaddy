<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class SecurityEvent extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id',
        'event_type',
        'event_status',
        'event_source',
        'ip_address',
        'user_agent',
        'session_id',
        'email',
        'organization_id',
        'country',
        'city',
        'region',
        'metadata',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * Event type constants
     */
    const TYPE_LOGIN_SUCCESS = 'login_success';
    const TYPE_LOGIN_FAILURE = 'login_failure';
    const TYPE_LOGOUT = 'logout';
    const TYPE_PASSWORD_CHANGE = 'password_change';
    const TYPE_PASSWORD_RESET_REQUEST = 'password_reset_request';
    const TYPE_PASSWORD_RESET_COMPLETE = 'password_reset_complete';
    const TYPE_MFA_ENABLE = 'mfa_enable';
    const TYPE_MFA_DISABLE = 'mfa_disable';
    const TYPE_MFA_SUCCESS = 'mfa_success';
    const TYPE_MFA_FAILURE = 'mfa_failure';
    const TYPE_SESSION_REVOKED = 'session_revoked';
    const TYPE_ACCOUNT_LOCKED = 'account_locked';
    const TYPE_ACCOUNT_UNLOCKED = 'account_unlocked';
    const TYPE_SUSPICIOUS_ACTIVITY = 'suspicious_activity';
    const TYPE_GOOGLE_LOGIN = 'google_login';
    const TYPE_WHATSAPP_LOGIN = 'whatsapp_login';
    const TYPE_API_TOKEN_CREATED = 'api_token_created';
    const TYPE_API_TOKEN_REVOKED = 'api_token_revoked';

    /**
     * Relationship to User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log a security event
     */
    public static function log(
        string $eventType,
        string $status,
        ?User $user = null,
        ?Request $request = null,
        array $metadata = [],
        ?string $failureReason = null
    ): self {
        $request = $request ?? request();
        
        return self::create([
            'user_id' => $user?->id,
            'event_type' => $eventType,
            'event_status' => $status,
            'event_source' => $request->is('api/*') ? 'api' : 'web',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => session()->getId(),
            'email' => $user?->email ?? ($metadata['email'] ?? null),
            'organization_id' => $user?->current_organization_id ?? session('current_organization_id'),
            'metadata' => $metadata,
            'failure_reason' => $failureReason,
        ]);
    }

    /**
     * Log a successful login
     */
    public static function logLoginSuccess(User $user, ?Request $request = null, array $metadata = []): self
    {
        return self::log(
            self::TYPE_LOGIN_SUCCESS,
            'success',
            $user,
            $request,
            array_merge($metadata, [
                'login_method' => $metadata['login_method'] ?? 'email',
            ])
        );
    }

    /**
     * Log a failed login
     */
    public static function logLoginFailure(?string $email, string $reason, ?Request $request = null): self
    {
        return self::log(
            self::TYPE_LOGIN_FAILURE,
            'failure',
            null,
            $request,
            ['email' => $email],
            $reason
        );
    }

    /**
     * Log a logout
     */
    public static function logLogout(User $user, ?Request $request = null): self
    {
        return self::log(
            self::TYPE_LOGOUT,
            'success',
            $user,
            $request
        );
    }

    /**
     * Count failed login attempts from an IP in the last X minutes
     */
    public static function countFailedLoginsFromIp(string $ip, int $minutes = 15): int
    {
        return self::where('event_type', self::TYPE_LOGIN_FAILURE)
            ->where('ip_address', $ip)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    /**
     * Count failed login attempts for an email in the last X minutes
     */
    public static function countFailedLoginsForEmail(string $email, int $minutes = 15): int
    {
        return self::where('event_type', self::TYPE_LOGIN_FAILURE)
            ->where('email', $email)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    /**
     * Check if IP should be rate limited
     */
    public static function shouldRateLimitIp(string $ip, int $maxAttempts = 10, int $minutes = 15): bool
    {
        return self::countFailedLoginsFromIp($ip, $minutes) >= $maxAttempts;
    }

    /**
     * Check if email should be rate limited
     */
    public static function shouldRateLimitEmail(string $email, int $maxAttempts = 5, int $minutes = 15): bool
    {
        return self::countFailedLoginsForEmail($email, $minutes) >= $maxAttempts;
    }

    /**
     * Get recent security events for a user
     */
    public static function getRecentForUser(string $userId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get suspicious events (multiple failures, unusual locations, etc.)
     */
    public static function getSuspiciousEvents(int $hours = 24): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('event_status', 'failure')
            ->where('created_at', '>=', now()->subHours($hours))
            ->selectRaw('ip_address, COUNT(*) as failure_count')
            ->groupBy('ip_address')
            ->having('failure_count', '>=', 5)
            ->get();
    }
}

