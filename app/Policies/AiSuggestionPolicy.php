<?php

namespace App\Policies;

use App\Models\AiSuggestion;
use App\Models\User;

class AiSuggestionPolicy
{

    /**
     * Determine whether the user can view any AI suggestions.
     *
     * @param  User  $user  The authenticated user.
     * @return bool  True if a tenant context is bound, false otherwise.
     */
    public function viewAny(User $user): bool
    {
        return app()->bound('current.tenant');
    }


    /**
     * Determine whether the user can view a specific AI suggestion.
     *
     * @param  User         $user        The authenticated user.
     * @param  AiSuggestion $suggestion  The AI suggestion instance.
     * @return bool  True if the suggestion belongs to the current tenant, aborts(404) if not.
     */
    public function view(User $user, AiSuggestion $suggestion): bool
    {
        if (! app()->bound('current.tenant')) {
            return false;
        }
        if ($suggestion->tenant_id !== app('current.tenant')->id) {
            abort(404);
        }
        return true;
    }


    /**
     * Determine whether the user can accept an AI suggestion.
     *
     * @param  User         $user        The authenticated user.
     * @param  AiSuggestion $suggestion  The AI suggestion instance.
     * @return bool  True if the user can view the suggestion.
     */
    public function accept(User $user, AiSuggestion $suggestion): bool
    {
        return $this->view($user, $suggestion);
    }


    /**
     * Determine whether the user can dismiss an AI suggestion.
     *
     * @param  User         $user        The authenticated user.
     * @param  AiSuggestion $suggestion  The AI suggestion instance.
     * @return bool  True if the user can view the suggestion.
     */
    public function dismiss(User $user, AiSuggestion $suggestion): bool
    {
        return $this->view($user, $suggestion);
    }


    /**
     * Determine whether the user can postpone an AI suggestion.
     *
     * @param  User         $user        The authenticated user.
     * @param  AiSuggestion $suggestion  The AI suggestion instance.
     * @return bool  True if the user can view the suggestion.
     */
    public function postpone(User $user, AiSuggestion $suggestion): bool
    {
        return $this->view($user, $suggestion);
    }
}