<?php

namespace App\Http\Middleware;

use App\Http\Resources\TenantResource;
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
        // For plain JSON API calls (Axios from the SPA), skip the expensive
        // tenant queries — the data is never sent to the client for these
        // requests. Only Inertia page renders (full load or X-Inertia visit)
        // need the full shared props.
        if ($request->expectsJson() && ! $request->hasHeader('X-Inertia')) {
            return parent::share($request);
        }

        $activeTenant = session('active_tenant');

        // Auto-select the first available tenant when no active tenant is set in session.
        // This ensures freshly-logged-in users can see data without manually switching.
        if (! $activeTenant && $request->user()) {
            $firstTenant = $request->user()->tenants()->first();
            if ($firstTenant) {
                $activeTenant = $firstTenant->slug;
                session(['active_tenant' => $activeTenant]);
            }
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'tenants' => $request->user()
                    ? TenantResource::collection(
                        $request->user()->tenants()->withPivot('role')->get()
                    )
                    : [],
                'activeTenant' => $activeTenant,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
