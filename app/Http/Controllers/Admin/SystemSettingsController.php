<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SystemSettingsController extends Controller
{
    // Middleware is applied via route group in web.php

    public function index()
    {
        try {
            $openaiKey = PlatformSetting::get('openai_api_key');
            $anthropicKey = PlatformSetting::get('anthropic_api_key');
            
            return Inertia::render('Admin/SystemSettings', [
                'settings' => [
                    'ai_provider' => PlatformSetting::get('ai_provider', 'openai'),
                    'openai_api_key' => $openaiKey ? '••••••••••••' : null,
                    'openai_model' => PlatformSetting::get('openai_model', 'gpt-4o'),
                    'anthropic_api_key' => $anthropicKey ? '••••••••••••' : null,
                    'anthropic_model' => PlatformSetting::get('anthropic_model', 'claude-sonnet-4-20250514'),
                ],
            ]);
        } catch (\Exception $e) {
            // If there's an error loading settings, return defaults
            \Log::error('Error loading system settings: ' . $e->getMessage());
            
            return Inertia::render('Admin/SystemSettings', [
                'settings' => [
                    'ai_provider' => 'openai',
                    'openai_api_key' => null,
                    'openai_model' => 'gpt-4o',
                    'anthropic_api_key' => null,
                    'anthropic_model' => 'claude-sonnet-4-20250514',
                ],
            ]);
        }
    }

    public function update(Request $request)
    {
        $request->validate([
            'ai_provider' => 'required|in:openai,anthropic',
            'openai_api_key' => 'nullable|string',
            'openai_model' => 'nullable|string',
            'anthropic_api_key' => 'nullable|string',
            'anthropic_model' => 'nullable|string',
        ]);

        PlatformSetting::set('ai_provider', $request->ai_provider);

        if ($request->filled('openai_api_key') && $request->openai_api_key !== '••••••••••••') {
            PlatformSetting::set('openai_api_key', $request->openai_api_key, 'encrypted');
        }

        if ($request->filled('openai_model')) {
            PlatformSetting::set('openai_model', $request->openai_model);
        }

        if ($request->filled('anthropic_api_key') && $request->anthropic_api_key !== '••••••••••••') {
            PlatformSetting::set('anthropic_api_key', $request->anthropic_api_key, 'encrypted');
        }

        if ($request->filled('anthropic_model')) {
            PlatformSetting::set('anthropic_model', $request->anthropic_model);
        }

        return back()->with('success', 'Settings updated successfully');
    }

    public function testConnection(Request $request)
    {
        try {
            $ai = new \App\Services\AI\AIService();
            $response = $ai->ask('Hello! Please respond with "Connection successful"');

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'API connection successful!',
                    'response' => $response,
                ]);
            }

            return back()->with('success', 'API connection successful!');
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }

            return back()->withErrors(['message' => $e->getMessage()]);
        }
    }
}

