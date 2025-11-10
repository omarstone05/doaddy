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

        // Update cultural settings
        $settings = AddyCulturalSetting::getOrCreate($organization->id);
        $settings->update($request->only([
            'tone',
            'enable_predictions',
            'enable_proactive_suggestions',
            'max_daily_suggestions',
            'quiet_hours_start',
            'quiet_hours_end',
        ]));

        // Update user patterns
        $userPattern = AddyUserPattern::getOrCreate($organization->id, $request->user()->id);
        $userPattern->update($request->only([
            'work_style',
            'adhd_mode',
            'preferred_task_chunk_size',
        ]));

        return back()->with('success', 'Preferences updated successfully');
    }
}

