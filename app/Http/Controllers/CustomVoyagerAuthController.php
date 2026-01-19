<?php

namespace App\Http\Controllers;

use TCG\Voyager\Http\Controllers\VoyagerAuthController;
use Illuminate\Support\Facades\Auth;

class CustomVoyagerAuthController extends VoyagerAuthController
{
    /**
     * Where to redirect users after login.
     *
     * @return string
     */
    public function redirectTo()
    {
        $user = Auth::user();

        // Check if user has admin permission
        if ($user->hasPermission('browse_admin')) {
            return config('voyager.user.redirect', route('voyager.dashboard'));
        }
        // Redirect non-admin users to the calendar or specific route
        return '/reunion';
    }
}
