<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantSlug = $request->header('X-Tenant')
            ?? $request->query('tenant')
            ?? session('active_tenant');

        if (! $tenantSlug) {
            abort(400, 'No tenant context provided.');
        }

        $tenant = Tenant::where('slug', $tenantSlug)->first();

        if (! $tenant) {
            abort(404, 'Tenant not found.');
        }

        // Ensure the authenticated user belongs to this tenant
        if (! $request->user()->tenants()->where('tenants.id', $tenant->id)->exists()) {
            abort(403, 'Access denied to this tenant.');
        }

        app()->instance('current.tenant', $tenant);
        session(['active_tenant' => $tenant->slug]);

        return $next($request);
    }
}
