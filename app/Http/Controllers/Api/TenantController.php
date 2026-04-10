<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CreateTenantRequest;
use App\Http\Requests\Tenant\UpdateTenantRequest;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TenantController extends Controller
{
    public function __construct(private TenantService $tenantService) {}

    public function index(): AnonymousResourceCollection
    {
        $tenants = $this->tenantService->listForUser(request()->user());
        return TenantResource::collection($tenants);
    }

    public function store(CreateTenantRequest $request): TenantResource
    {
        $tenant = $this->tenantService->create($request->user(), $request->validated());
        return new TenantResource($tenant);
    }

    public function show(Tenant $tenant): TenantResource
    {
        $this->authorize('view', $tenant);
        return new TenantResource($tenant);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): TenantResource
    {
        $this->authorize('update', $tenant);
        $tenant = $this->tenantService->update($tenant, $request->validated());
        return new TenantResource($tenant);
    }
}
