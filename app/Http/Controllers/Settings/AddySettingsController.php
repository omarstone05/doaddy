<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AddyCulturalSetting;
use App\Models\AddyUserPattern;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AddySettingsController extends Controller
{
    public function index(Request $request)
    {
        $organization = $request->user()->organization;
        $settings = AddyCulturalSetting::getOrCreate($organization->id);
        $userPattern = AddyUserPattern::getOrCreate($organization->id, $request->user()->id);

        return Inertia::render('Settings/AddySettings', [
            'settings' => $settings,
            'userPattern' => $userPattern,
        ]);
    }

    public function update(Request $request)
    {
        $organization = $request->user()->organization;

        \Log::info('Addy settings update request', [
            'all_data' => $request->all(),
            'organization_id' => $organization->id,
        ]);

        // Update cultural settings
        $settings = AddyCulturalSetting::getOrCreate($organization->id);
        $settingsData = $request->only([
            'tone',
            'enable_predictions',
            'enable_proactive_suggestions',
            'max_daily_suggestions',
            'quiet_hours_start',
            'quiet_hours_end',
        ]);
        
        \Log::info('Settings data before processing', ['data' => $settingsData]);
        
        // Convert boolean strings to actual booleans
        if (isset($settingsData['enable_predictions'])) {
            $settingsData['enable_predictions'] = filter_var($settingsData['enable_predictions'], FILTER_VALIDATE_BOOLEAN);
        }
        if (isset($settingsData['enable_proactive_suggestions'])) {
            $settingsData['enable_proactive_suggestions'] = filter_var($settingsData['enable_proactive_suggestions'], FILTER_VALIDATE_BOOLEAN);
        }
        
        // Handle time fields - convert empty strings to null
        if (isset($settingsData['quiet_hours_start']) && empty($settingsData['quiet_hours_start'])) {
            $settingsData['quiet_hours_start'] = null;
        }
        if (isset($settingsData['quiet_hours_end']) && empty($settingsData['quiet_hours_end'])) {
            $settingsData['quiet_hours_end'] = null;
        }
        
        \Log::info('Settings data after processing', ['data' => $settingsData]);
        
        $settings->update($settingsData);
        
        if (!empty($settingsData['tone']) && $organization->tone_preference !== $settingsData['tone']) {
            $organization->update(['tone_preference' => $settingsData['tone']]);
        }
        
        \Log::info('Settings updated', ['settings_id' => $settings->id, 'tone' => $settings->tone]);

        // Update user patterns
        $userPattern = AddyUserPattern::getOrCreate($organization->id, $request->user()->id);
        $patternData = $request->only([
            'work_style',
            'adhd_mode',
            'preferred_task_chunk_size',
        ]);
        
        // Convert boolean strings to actual booleans
        if (isset($patternData['adhd_mode'])) {
            $patternData['adhd_mode'] = filter_var($patternData['adhd_mode'], FILTER_VALIDATE_BOOLEAN);
        }
        
        // Convert integer strings to integers
        if (isset($patternData['preferred_task_chunk_size'])) {
            $patternData['preferred_task_chunk_size'] = (int) $patternData['preferred_task_chunk_size'];
        }
        
        $userPattern->update($patternData);

        // Reload settings to return fresh data
        $settings->refresh();
        $userPattern->refresh();

        return redirect()->route('settings.addy')->with('success', 'Preferences updated successfully');
    }
}
