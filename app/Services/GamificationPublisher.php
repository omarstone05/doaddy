<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GamificationPublisher
{
    public function publish(string $eventType, array $metadata = []): void
    {
        $user = auth()->user();
        if (!$user || !$user->penda_account_id) {
            return;
        }

        $baseUrl = config('services.penda_cloud.url', config('services.penda_sso.base_url'));
        $token = config('services.penda_cloud.api_token') ?: config('services.penda_sso.service_token');

        if (!$baseUrl || !$token) {
            return;
        }

        try {
            Http::withToken($token)
                ->timeout(5)
                ->post(rtrim($baseUrl, '/') . '/api/gamification/events', [
                    'penda_account_id' => $user->penda_account_id,
                    'app_code' => config('app.code', 'addy'),
                    'event_type' => $eventType,
                    'metadata' => $metadata,
                ]);
        } catch (\Exception $e) {
            Log::warning('Gamification publish failed', [
                'event' => $eventType,
                'user_id' => $user->id,
                'penda_account_id' => $user->penda_account_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
