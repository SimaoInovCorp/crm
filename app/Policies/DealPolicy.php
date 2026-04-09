<?php

namespace App\Policies;

use App\Models\Deal;
use App\Models\User;

class DealPolicy
{

    /**
     * Determine whether the user can view any deals.
     *
     * @param  User  $user  The authenticated user.
     * @return bool  True if a tenant context is bound, false otherwise.
     */
    public function viewAny(User $user): bool
    {
        return app()->bound('current.tenant');
    }


    /**
     * Determine whether the user can view a specific deal.
     *
     * @param  User $user The authenticated user.
     * @param  Deal $deal The deal instance.
     * @return bool  True if the deal belongs to the current tenant, aborts(404) if not.
     */
    public function view(User $user, Deal $deal): bool
    {
        if (! app()->bound('current.tenant')) {
            return false;
        }
        if ($deal->tenant_id !== app('current.tenant')->id) {
            abort(404);
        }
        return true;
    }


    /**
     * Determine whether the user can create a deal.
     *
     * @param  User  $user  The authenticated user.
     * @return bool  True if a tenant context is bound, false otherwise.
     */
    public function create(User $user): bool
    {
        return app()->bound('current.tenant');
    }


    /**
     * Determine whether the user can update a deal.
     *
     * @param  User $user The authenticated user.
     * @param  Deal $deal The deal instance.
     * @return bool  True if the deal belongs to the current tenant, aborts(404) if not.
     */
    public function update(User $user, Deal $deal): bool
    {
        if (! app()->bound('current.tenant')) {
            return false;
        }
        if ($deal->tenant_id !== app('current.tenant')->id) {
            abort(404);
        }
        return true;
    }


    /**
     * Determine whether the user can delete a deal.
     * Only tenant owners or admins can delete.
     *
     * @param  User $user The authenticated user.
     * @param  Deal $deal The deal instance.
     * @return bool  True if the user is owner/admin and deal belongs to tenant, aborts(404) if not.
     */
    public function delete(User $user, Deal $deal): bool
    {
        if (! app()->bound('current.tenant')) {
            return false;
        }
        if ($deal->tenant_id !== app('current.tenant')->id) {
            abort(404);
        }
        $role = $user->tenants()
            ->where('tenants.id', app('current.tenant')->id)
            ->first()?->pivot?->role;
        return in_array($role, ['owner', 'admin']);
    }
}
