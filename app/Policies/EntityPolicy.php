<?php

namespace App\Policies;

use App\Models\Entity;
use App\Models\User;

class EntityPolicy
{

    /**
     * Determine whether the user can view any entities.
     * Any member of the active tenant can list entities.
     *
     * @param  User  $user  The authenticated user.
     * @return bool  True if a tenant context is bound, false otherwise.
     */
    public function viewAny(User $user): bool
    {
        return app()->bound('current.tenant');
    }


    /**
     * Determine whether the user can view a specific entity.
     * Any member of the active tenant can view an entity (HasTenant scope enforces isolation).
     *
     * @param  User   $user   The authenticated user.
     * @param  Entity $entity The entity instance.
     * @return bool  True if the entity belongs to the current tenant, aborts(404) if not.
     */
    public function view(User $user, Entity $entity): bool
    {
        if (! app()->bound('current.tenant')) {
            return false;
        }
        if ($entity->tenant_id !== app('current.tenant')->id) {
            abort(404);
        }
        return true;
    }


    /**
     * Determine whether the user can create an entity.
     * Any member can create an entity within the current tenant.
     *
     * @param  User  $user  The authenticated user.
     * @return bool  True if a tenant context is bound, false otherwise.
     */
    public function create(User $user): bool
    {
        return app()->bound('current.tenant');
    }


    /**
     * Determine whether the user can update an entity.
     * Any member can update an entity.
     *
     * @param  User   $user   The authenticated user.
     * @param  Entity $entity The entity instance.
     * @return bool  True if the entity belongs to the current tenant, aborts(404) if not.
     */
    public function update(User $user, Entity $entity): bool
    {
        if (! app()->bound('current.tenant')) {
            return false;
        }
        if ($entity->tenant_id !== app('current.tenant')->id) {
            abort(404);
        }
        return true;
    }


    /**
     * Determine whether the user can delete an entity.
     * Only owner/admin can delete.
     *
     * @param  User   $user   The authenticated user.
     * @param  Entity $entity The entity instance.
     * @return bool  True if the user is owner/admin and entity belongs to tenant, aborts(404) if not.
     */
    public function delete(User $user, Entity $entity): bool
    {
        if (! app()->bound('current.tenant')) {
            return false;
        }
        if ($entity->tenant_id !== app('current.tenant')->id) {
            abort(404);
        }
        $role = $user->tenants()
            ->where('tenants.id', app('current.tenant')->id)
            ->first()?->pivot?->role;
        return in_array($role, ['owner', 'admin']);
    }
}
