<?php

namespace App\Http\Controllers;

use App\Services\GoogleDriveService;
use Illuminate\Http\Request;

class GoogleAuthController extends Controller
{
    public function redirect(GoogleDriveService $driveService)
    {
        return redirect($driveService->getAuthUrl());
    }

    public function callback(Request $request, GoogleDriveService $driveService)
    {
        $code = $request->get('code');

        if (!$code) {
            return redirect('/settings')->with('error', 'Failed to connect Google Drive: No authorization code received');
        }

        if ($driveService->handleCallback($code)) {
            return redirect('/settings')->with('success', 'Google Drive connected successfully!');
        }

        return redirect('/settings')->with('error', 'Failed to connect Google Drive');
    }
}

