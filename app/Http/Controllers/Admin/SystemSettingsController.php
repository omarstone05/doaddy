<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SystemSettingsController extends Controller
{
    public function __construct()
    {
        // Add middleware to ensure only super admin can access
        $this->middleware(function ($request, $next) {
            if (!$request->user() || !$request->user()->is_super_admin) {
                abort(403, 'Unauthorized');
            }
            return $next($request);
        });
    }

    public function index()
    {
        return Inertia::render('Admin/SystemSettings', [
            'settings' => [
                'ai_provider' => PlatformSetting::get('ai_provider', 'openai'),
                'openai_api_key' => PlatformSetting::get('openai_api_key') ? '••••••••••••' : null,
                'openai_model' => PlatformSetting::get('openai_model', 'gpt-4o'),
                'anthropic_api_key' => PlatformSetting::get('anthropic_api_key') ? '••••••••••••' : null,
                'anthropic_model' => PlatformSetting::get('anthropic_model', 'claude-sonnet-4-20250514'),
            ],
        ]);
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

            return response()->json([
                'success' => true,
                'message' => 'API connection successful!',
                'response' => $response,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}

