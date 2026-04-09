<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{

    /**
     * Determine whether the user can view any tenants.
     * Any authenticated user can list their own tenants.
     *
     * @param  User  $user  The authenticated user.
     * @return bool  Always true (all users can view their tenants).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }


    /**
     * Determine whether the user can view a specific tenant.
     * User can view a tenant they belong to.
     *
     * @param  User   $user   The authenticated user.
     * @param  Tenant $tenant The tenant instance.
     * @return bool  True if the user belongs to the tenant, false otherwise.
     */
    public function view(User $user, Tenant $tenant): bool
    {
        return $user->tenants()->where('tenants.id', $tenant->id)->exists();
    }


    /**
     * Determine whether the user can create a tenant.
     * Any authenticated user can create a tenant.
     *
     * @param  User  $user  The authenticated user.
     * @return bool  Always true (all users can create tenants).
     */
    public function create(User $user): bool
    {
        return true;
    }


    /**
     * Determine whether the user can update a tenant.
     * Only the tenant owner or admin can update settings.
     *
     * @param  User   $user   The authenticated user.
     * @param  Tenant $tenant The tenant instance.
     * @return bool  True if the user is owner/admin of the tenant, false otherwise.
     */
    public function update(User $user, Tenant $tenant): bool
    {
        $role = $user->tenants()
            ->where('tenants.id', $tenant->id)
            ->first()?->pivot?->role;

        return in_array($role, ['owner', 'admin']);
    }


    /**
     * Determine whether the user can delete a tenant.
     * Only the tenant owner can delete a tenant.
     *
     * @param  User   $user   The authenticated user.
     * @param  Tenant $tenant The tenant instance.
     * @return bool  True if the user is the owner of the tenant, false otherwise.
     */
    public function delete(User $user, Tenant $tenant): bool
    {
        return $tenant->owner_id === $user->id;
    }
}
