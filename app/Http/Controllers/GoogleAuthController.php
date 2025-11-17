<?php

namespace App\Http\Controllers;

use App\Services\GoogleDriveService;
use Illuminate\Http\Request;

class GoogleAuthController extends Controller
{
    public function redirect(GoogleDriveService $driveService)
    {
        try {
            $authUrl = $driveService->getAuthUrl();
            return redirect($authUrl);
        } catch (\Exception $e) {
            \Log::error('Google Drive redirect failed', ['error' => $e->getMessage()]);
            return redirect('/settings')->with('error', 'Failed to initialize Google Drive authentication: ' . $e->getMessage());
        }
    }

    public function callback(Request $request, GoogleDriveService $driveService)
    {
        $code = $request->get('code');

        if (!$code) {
            \Log::warning('Google Drive callback missing code', ['request' => $request->all()]);
            return redirect('/login')->with('error', 'Failed to connect Google Drive: No authorization code received. Please try again.');
        }

        try {
            if ($driveService->handleCallback($code)) {
                // Check if user is authenticated, redirect accordingly
                if (auth()->check()) {
                    return redirect('/settings')->with('success', 'Google Drive connected successfully!');
                }
                return redirect('/login')->with('success', 'Google Drive connected successfully! Please log in.');
            }

            return redirect('/login')->with('error', 'Failed to connect Google Drive');
        } catch (\Exception $e) {
            \Log::error('Google Drive callback failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect('/login')->with('error', 'Failed to connect Google Drive: ' . $e->getMessage());
        }
    }
}

