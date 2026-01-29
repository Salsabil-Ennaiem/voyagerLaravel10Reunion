<?php

namespace App\Policies;

use App\Models\Reunion;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReunionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Filtering happens in controller query
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Reunion $reunion): bool
    {
        return $user->isAdmin() || 
               $user->isChefIn($reunion->organisation_id) || 
               $reunion->invitations()->where('email', $user->email)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Logic from controller: Members cannot create, only Admin or Chef
        // Controller checked: if (!$isAdmin && $userRole === 'membre') -> 403
        // userRole was fetched via attributes. 
        // We will trust isAdmin() or isChef() (meaning they lead at least one org)
        return $user->isAdmin() || $user->isChef();
    }
        public function gestion(User $user, Reunion $reunion): bool
    {
        return $user->isAdmin() || $user->isChefIn($reunion->organisation_id);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Reunion $reunion): bool
    {
        return $user->isAdmin() || $user->isChefIn($reunion->organisation_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Reunion $reunion): bool
    {
        return $user->isAdmin() || $user->isChefIn($reunion->organisation_id);
    }
}
