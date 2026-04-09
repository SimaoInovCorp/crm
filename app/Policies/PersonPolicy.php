<?php

namespace App\Policies;

use App\Models\Person;
use App\Models\User;

class PersonPolicy
{

    /**
     * Determine whether the user can view any people.
     *
     * @param  User  $user  The authenticated user.
     * @return bool  True if a tenant context is bound, false otherwise.
     */
    public function viewAny(User $user): bool
    {
        return app()->bound('current.tenant');
    }


    /**
     * Determine whether the user can view a specific person.
     *
     * @param  User   $user   The authenticated user.
     * @param  Person $person The person instance.
     * @return bool  True if the person belongs to the current tenant, aborts(404) if not.
     */
    public function view(User $user, Person $person): bool
    {
        if (! app()->bound('current.tenant')) {
            return false;
        }
        if ($person->tenant_id !== app('current.tenant')->id) {
            abort(404);
        }
        return true;
    }


    /**
     * Determine whether the user can create a person.
     *
     * @param  User  $user  The authenticated user.
     * @return bool  True if a tenant context is bound, false otherwise.
     */
    public function create(User $user): bool
    {
        return app()->bound('current.tenant');
    }


    /**
     * Determine whether the user can update a person.
     *
     * @param  User   $user   The authenticated user.
     * @param  Person $person The person instance.
     * @return bool  True if the person belongs to the current tenant, aborts(404) if not.
     */
    public function update(User $user, Person $person): bool
    {
        if (! app()->bound('current.tenant')) {
            return false;
        }
        if ($person->tenant_id !== app('current.tenant')->id) {
            abort(404);
        }
        return true;
    }


    /**
     * Determine whether the user can delete a person.
     * Only tenant owners or admins can delete.
     *
     * @param  User   $user   The authenticated user.
     * @param  Person $person The person instance.
     * @return bool  True if the user is owner/admin and person belongs to tenant, aborts(404) if not.
     */
    public function delete(User $user, Person $person): bool
    {
        if (! app()->bound('current.tenant')) {
            return false;
        }
        if ($person->tenant_id !== app('current.tenant')->id) {
            abort(404);
        }
        $role = $user->tenants()
            ->where('tenants.id', app('current.tenant')->id)
            ->first()?->pivot?->role;
        return in_array($role, ['owner', 'admin']);
    }
}
