<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\SwitchTenantRequest;
use App\Http\Resources\TenantResource;
use App\Services\TenantService;

class TenantSwitchController extends Controller
{
    public function __construct(private TenantService $tenantService) {}

    public function __invoke(SwitchTenantRequest $request): TenantResource
    {
        $tenant = $this->tenantService->switchActiveTenant(
            $request->user(),
            $request->validated('slug')
        );

        return new TenantResource($tenant);
    }
}
