<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminSettingsController extends Controller
{
    public function index()
    {
        $settings = PlatformSetting::orderBy('group')->orderBy('key')->get()
            ->groupBy('group')
            ->map(function ($groupSettings) {
                return $groupSettings->map(function ($setting) {
                    return [
                        'key' => $setting->key,
                        'value' => $setting->getValue(),
                        'type' => $setting->type,
                        'label' => $setting->label,
                        'description' => $setting->description,
                    ];
                });
            });

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($validated['settings'] as $key => $value) {
            $setting = PlatformSetting::where('key', $key)->first();
            
            if ($setting) {
                $oldValue = $setting->getValue();
                $setting->setValue($value);
                $setting->save();
                
                // Clear cache
                \Cache::forget("platform_setting_{$key}");
                
                // Log activity
                \App\Models\AdminActivityLog::log('updated', $setting, ['value' => $oldValue], ['value' => $value]);
            }
        }

        return back()->with('success', 'Settings updated successfully');
    }
}

