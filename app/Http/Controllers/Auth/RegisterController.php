<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\DashboardCard;
use App\Models\Organization;
use App\Models\OrgDashboardCard;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class RegisterController extends Controller
{
    public function show()
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request)
    {
        // All registration and onboarding is now handled by Penda Cloud
        // Redirect to Penda Cloud registration
        $pendaCloudUrl = config('services.penda_sso.base_url', 'https://penda.cloud');
        return redirect($pendaCloudUrl . '/register');
    }

    private function createDefaultDashboardCards($organizationId)
    {
        $defaultCards = DashboardCard::where('is_default', true)->get();
        
        foreach ($defaultCards as $index => $card) {
            try {
                OrgDashboardCard::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $organizationId,
                    'dashboard_card_id' => $card->id,
                    'display_order' => $index,
                    'is_visible' => true,
                    'width' => 8, // Default width
                    'height' => 8, // Default height
                    'row' => null, // Will be auto-positioned by frontend
                    'col' => null, // Will be auto-positioned by frontend
                ]);
            } catch (\Exception $e) {
                // Log but don't fail registration if card creation fails
                \Log::warning('Failed to create dashboard card for organization', [
                    'organization_id' => $organizationId,
                    'card_key' => $card->key,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
