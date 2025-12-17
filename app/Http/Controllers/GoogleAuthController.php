<?php

namespace App\Http\Controllers;

use App\Services\GoogleDriveService;
use Illuminate\Http\Request;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return redirect('/login')->with('error', 'Please log in to connect Google Drive.');
            }

            $driveService = new GoogleDriveService($user);
            $authUrl = $driveService->getAuthUrl();
            return redirect($authUrl);
        } catch (\Exception $e) {
            \Log::error('Google Drive redirect failed', ['error' => $e->getMessage()]);
            return redirect('/settings')->with('error', 'Failed to initialize Google Drive authentication: ' . $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        $code = $request->get('code');

        if (!$code) {
            \Log::warning('Google Drive callback missing code', ['request' => $request->all()]);
            return redirect('/login')->with('error', 'Failed to connect Google Drive: No authorization code received. Please try again.');
        }

        try {
            $user = auth()->user();
            if (!$user) {
                return redirect('/login')->with('error', 'Please log in to connect Google Drive.');
            }

            // Create service instance with current user
            $driveService = new GoogleDriveService($user);
            
            if ($driveService->handleCallback($code, $user)) {
                return redirect('/settings')->with('success', 'Google Drive connected successfully!');
            }

            return redirect('/settings')->with('error', 'Failed to connect Google Drive');
        } catch (\Exception $e) {
            \Log::error('Google Drive callback failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect('/settings')->with('error', 'Failed to connect Google Drive: ' . $e->getMessage());
        }
    }
}

