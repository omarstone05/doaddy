<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class AdminProfileController extends Controller
{
    public function show()
    {
        return Inertia::render('Admin/Profile', [
            'user' => Auth::user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        // Track changes
        $oldValues = $user->only(array_keys($validated));
        $changes = [];
        foreach ($validated as $key => $value) {
            if (isset($oldValues[$key]) && $oldValues[$key] != $value) {
                $fieldName = ucfirst(str_replace('_', ' ', $key));
                $changes[$fieldName] = $value;
            }
        }

        $user->update($validated);

        // Send email notification if profile was updated
        if (!empty($changes)) {
            try {
                $emailService = app(\App\Services\Admin\EmailService::class);
                $organization = $user->organization;
                
                if ($organization) {
                    $changesList = [];
                    foreach ($changes as $field => $value) {
                        $changesList[] = "- {$field}: {$value}";
                    }
                    
                    $emailService->sendProfileUpdateNotification($user, $changes);
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to send profile update email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', 'Profile updated successfully');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => ['required', function ($attribute, $value, $fail) use ($user) {
                if (!Hash::check($value, $user->password)) {
                    $fail('The current password is incorrect.');
                }
            }],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password changed successfully');
    }
}

