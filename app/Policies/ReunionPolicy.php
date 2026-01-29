<?php

namespace App\Policies;

use App\Models\Reunion;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReunionPolicy
{
    /**
     * Determine whether the user can view any models.
     * All authenticated users can view the list (filtering happens in controller).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * 
     * - Admin can view any reunion
     * - Chef can view reunions of their organisation
     * - Member can only view reunions they are invited to
     */
    public function view(User $user, Reunion $reunion): bool
    {
        // Admin can view any reunion
        if ($user->isAdmin()) {
            return true;
        }

        // Chef can view all reunions in their organisation
        if ($user->isChefOf($reunion->organisation_id)) {
            return true;
        }

        // Members can only view if they are invited
        return $reunion->invitations()->where('email', $user->email)->exists();
    }

    /**
     * Determine whether the user can create models.
     * 
     * - Admin can create reunions (for any organisation)
     * - Chef can create reunions (for their organisation only)
     * - Members cannot create
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isChef();
    }

    /**
     * Determine whether the user can create a reunion for a specific organisation.
     * This is used when creating to verify the user has rights on the target org.
     */
    public function createForOrganisation(User $user, int $organisationId): bool
    {
        // Admin can create for any organisation
        if ($user->isAdmin()) {
            return true;
        }

        // Chef can only create for their own organisation
        return $user->isChefOf($organisationId);
    }

    /**
     * Determine whether the user can update the model.
     * 
     * - Admin can update any reunion
     * - Chef can only update reunions of their organisation
     * - Members cannot update
     */
    public function update(User $user, Reunion $reunion): bool
    {
        // Admin can update any reunion
        if ($user->isAdmin()) {
            return true;
        }

        // Chef can only update reunions from their organisation
        return $user->isChefOf($reunion->organisation_id);
    }

    /**
     * Determine whether the user can delete the model.
     * 
     * - Admin can delete any reunion
     * - Chef can only delete reunions of their organisation
     * - Members cannot delete
     */
    public function delete(User $user, Reunion $reunion): bool
    {
        // Admin can delete any reunion
        if ($user->isAdmin()) {
            return true;
        }

        // Chef can only delete reunions from their organisation
        return $user->isChefOf($reunion->organisation_id);
    }

    /**
     * Determine whether the user can manage (edit/delete) the model.
     * Alias for internal use combining update/delete logic.
     */
    public function gestion(User $user, Reunion $reunion): bool
    {
        return $this->update($user, $reunion);
    }

    /**
     * Determine whether the user can export reunions.
     * Everyone can export the reunions they can see.
     */
    public function export(User $user): bool
    {
        return true;
    }
}
