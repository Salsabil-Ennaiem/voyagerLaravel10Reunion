<?php

namespace App\Http\Controllers;

use App\Models\Organisation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OrganisationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        if ($user->isAdmin()) {
            $organisations = Organisation::with('chef')->get();
            return view('organisations.index', compact('organisations'));
        }

        // For Chef or Member: show organizations they are part of
        $asChef = $user->chefOfOrganisations;
        $asMember = $user->memberOfOrganisations;
        
        $organisations = $asChef->merge($asMember)->unique('id');

        return view('organisations.index', compact('organisations'));
    }

    public function show(Organisation $organisation)
    {
        $user = Auth::user();
        $isChef = $user->isAdmin() || $organisation->chef_organisation_id === $user->id;
        $isMember = $user->isMemberIn($organisation->id);

        if (!$isChef && !$isMember) {
            abort(403);
        }

        $organisation->load('chef', 'members');

        $allUsers = [];
        if ($isChef) {
            $allUsers = \App\Models\User::where('actif', true)->get();
        }

        return view('organisations.show', compact('organisation', 'isChef', 'allUsers'));
    }

    public function switch(Request $request)
    {
        $request->validate(['organisation_id' => 'required|exists:organisations,id']);
        $orgId = $request->organisation_id;
        $user = Auth::user();

        // Check if user has access
        $hasAccess = $user->isAdmin() || $user->isChefIn($orgId) || $user->isMemberIn($orgId);
        
        if (!$hasAccess) {
            return back()->with('error', 'Vous n\'avez pas accès à cette organisation.');
        }

        session(['active_organisation_id' => $orgId]);

        return back()->with('success', 'Organisation choisie : ' . Organisation::find($orgId)->nom);
    }

    public function myOrganisation()
    {
        $user = Auth::user();
        $activeId = $user->getActiveOrganisationId();
        
        if ($activeId) {
            return redirect()->route('organisations.show', $activeId);
        }

        $organisation = $user->chefOfOrganisations()->first() ?? $user->memberOfOrganisations()->first();

        if (!$organisation) {
                abort(404, "Aucune organisation trouvée.");
        }

        return redirect()->route('organisations.show', $organisation->id);
    }

    public function update(Request $request, Organisation $organisation)
    {
        $user = Auth::user();

        // Security check: Only Admins or the actual Chef can update
        if (!$user->isAdmin() && $organisation->chef_organisation_id !== $user->id) {
            abort(403, "Seul le Chef peut modifier les informations.");
        }

        $rules = [
            'nom' => 'required|string|max:255',
            'email_contact' => 'nullable|email|max:255',
            'adresse' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];

        if ($user->isAdmin()) {
            $rules['chef_organisation_id'] = 'nullable|exists:users,id';
        }

        $request->validate($rules);

        $data = $request->only(['nom', 'email_contact', 'adresse', 'description']);

        if ($user->isAdmin() && $request->has('chef_organisation_id')) {
            $data['chef_organisation_id'] = $request->chef_organisation_id;
        }

        if ($request->hasFile('image')) {
            // Delete old image if it's a local path
            if ($organisation->image && !str_starts_with($organisation->image, 'http')) {
                Storage::disk('public')->delete($organisation->image);
            }
            $path = $request->file('image')->store('organisations', 'public');
            $data['image'] = $path;
        }

        $organisation->update($data);

        return back()->with('success', 'Organisation mise à jour avec succès.');
    }

    public function addMember(Request $request, Organisation $organisation)
    {
        $adminOrChef = Auth::user();
        if (!$adminOrChef->isAdmin() && $organisation->chef_organisation_id !== $adminOrChef->id) {
            abort(403);
        }

        $request->validate([
            'email' => 'required|email',
            'fonction' => 'nullable|string|max:255',
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();
        $wasCreated = false;
        
        if (!$user) {
            $wasCreated = true;
            $nameParts = explode('@', $request->email);
            $username = $nameParts[0];

            $user = \App\Models\User::create([
                'email' => $request->email,
                'nom' => strtoupper($username),
                'prenom' => ucfirst($username),
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'actif' => true,
                'role_id' => 2,
            ]);
        }

        $organisation->members()->syncWithoutDetaching([
            $user->id => ['fonction' => $request->fonction]
        ]);

        $msg = $wasCreated ? "Utilisateur créé et ajouté comme membre." : "Membre ajouté avec succès.";
        return back()->with('success', $msg);
    }

    public function updateMember(Request $request, Organisation $organisation, \App\Models\User $member)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $organisation->chef_organisation_id !== $user->id) {
            abort(403);
        }

        $request->validate([
            'fonction' => 'nullable|string|max:255',
        ]);

        $organisation->members()->updateExistingPivot($member->id, [
            'fonction' => $request->fonction
        ]);

        return back()->with('success', 'Membre mis à jour.');
    }

    public function removeMember(Organisation $organisation, \App\Models\User $member)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $organisation->chef_organisation_id !== $user->id) {
            abort(403);
        }

        $organisation->members()->detach($member->id);

        return back()->with('success', 'Membre retiré de l\'organisation.');
    }
}
