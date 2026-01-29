<?php

namespace App\Policies;

use App\Models\Organisation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrganisationPolicy
{
    /**
     * Determine whether the user can view any organisations.
     * All authenticated users can view the list of organisations.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the organisation details.
     * Admin, Chef of the organisation, or Members of the organisation can view.
     */
    public function view(User $user, Organisation $organisation): bool
    {
        // Admin can view any organisation
        if ($user->isAdmin()) {
            return true;
        }

        // Chef of this organisation can view
        if ($user->isChefOf($organisation->id)) {
            return true;
        }

        // Member of this organisation can view
        if ($user->isMemberOf($organisation->id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create organisations.
     * Only Admin can create new organisations.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the organisation.
     * Admin can update any organisation.
     * Chef can only update their own organisation.
     */
    public function update(User $user, Organisation $organisation): bool
    {
        // Admin can update any organisation
        if ($user->isAdmin()) {
            return true;
        }

        // Chef can only update their own organisation
        return $user->isChefOf($organisation->id);
    }

    /**
     * Determine whether the user can toggle the active status of the organisation.
     * Only Admin can activate/deactivate organisations.
     */
    public function toggleActive(User $user, Organisation $organisation): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the organisation.
     * Only Admin can delete organisations.
     */
    public function delete(User $user, Organisation $organisation): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can manage members of the organisation.
     * Admin or Chef of the organisation can manage members.
     */
    public function manageMembers(User $user, Organisation $organisation): bool
    {
        return $user->isAdmin() || $user->isChefOf($organisation->id);
    }

    /**
     * Determine whether the user can change the chef of the organisation.
     * Only Admin can change the chef.
     */
    public function changeChef(User $user, Organisation $organisation): bool
    {
        return $user->isAdmin();
    }
}
