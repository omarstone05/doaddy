<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $unreadNotificationCount = 0;
        
        if ($user) {
            $unreadNotificationCount = \App\Models\Notification::where('user_id', $user->id)
                ->where('organization_id', $user->organization_id)
                ->where('is_read', false)
                ->count();
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'organization' => $user->organization ? [
                        'id' => $user->organization->id,
                        'name' => $user->organization->name,
                    ] : null,
                ] : null,
            ],
            'flash' => [
                'message' => $request->session()->get('message'),
                'error' => $request->session()->get('error'),
                'success' => $request->session()->get('success'),
            ],
            'unreadNotificationCount' => $unreadNotificationCount,
            'url' => $request->path(),
        ];
    }
}
