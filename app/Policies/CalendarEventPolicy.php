<?php

namespace App\Policies;

use App\Models\CalendarEvent;
use App\Models\User;

class CalendarEventPolicy
{

    /**
     * Determine whether the user can view any calendar events.
     *
     * @param  User  $user  The authenticated user.
     * @return bool  True if a tenant context is bound, false otherwise.
     */
    public function viewAny(User $user): bool
    {
        return app()->bound('current.tenant');
    }


    /**
     * Determine whether the user can view a specific calendar event.
     *
     * @param  User          $user          The authenticated user.
     * @param  CalendarEvent $calendarEvent The calendar event instance.
     * @return bool  True if the event belongs to the current tenant, aborts(404) if not.
     */
    public function view(User $user, CalendarEvent $calendarEvent): bool
    {
        if (! app()->bound('current.tenant')) {
            return false;
        }
        if ($calendarEvent->tenant_id !== app('current.tenant')->id) {
            abort(404);
        }
        return true;
    }


    /**
     * Determine whether the user can create a calendar event.
     *
     * @param  User  $user  The authenticated user.
     * @return bool  True if a tenant context is bound, false otherwise.
     */
    public function create(User $user): bool
    {
        return app()->bound('current.tenant');
    }


    /**
     * Determine whether the user can update a calendar event.
     *
     * @param  User          $user          The authenticated user.
     * @param  CalendarEvent $calendarEvent The calendar event instance.
     * @return bool  True if the event belongs to the current tenant, aborts(404) if not.
     */
    public function update(User $user, CalendarEvent $calendarEvent): bool
    {
        if (! app()->bound('current.tenant')) {
            return false;
        }
        if ($calendarEvent->tenant_id !== app('current.tenant')->id) {
            abort(404);
        }
        return true;
    }


    /**
     * Determine whether the user can delete a calendar event.
     * Only tenant owners or admins can delete.
     *
     * @param  User          $user          The authenticated user.
     * @param  CalendarEvent $calendarEvent The calendar event instance.
     * @return bool  True if the user is owner/admin and event belongs to tenant, aborts(404) if not.
     */
    public function delete(User $user, CalendarEvent $calendarEvent): bool
    {
        if (! app()->bound('current.tenant')) {
            return false;
        }
        if ($calendarEvent->tenant_id !== app('current.tenant')->id) {
            abort(404);
        }
        // Only owner/admin can delete
        $role = $user->tenants()
            ->where('tenants.id', app('current.tenant')->id)
            ->first()?->pivot?->role;
        return in_array($role, ['owner', 'admin']);
    }
}
