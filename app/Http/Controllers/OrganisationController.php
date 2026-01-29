<?php

namespace App\Http\Controllers;

use App\Models\Organisation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class OrganisationController extends Controller
{
    public function __construct()
    {
        // Apply auth middleware to all methods
        $this->middleware('auth');
    }

    /**
     * Display a listing of organisations.
     * All users can view organisations they have access to.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Admin can see all organisations
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

    /**
     * Show the form for creating a new organisation.
     * Only Admin can access this.
     */
    public function create()
    {
        $this->authorize('create', Organisation::class);
        
        $users = User::where('actif', true)->get();
        return view('organisations.create', compact('users'));
    }

    /**
     * Store a newly created organisation.
     * Only Admin can create organisations.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Organisation::class);

        $request->validate([
            'nom' => 'required|string|max:150',
            'email_contact' => 'nullable|email|max:255',
            'adresse' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'chef_organisation_id' => 'nullable|exists:users,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'active' => 'nullable|boolean',
        ]);

        $data = $request->only(['nom', 'email_contact', 'adresse', 'description', 'chef_organisation_id']);
        $data['active'] = $request->has('active') ? $request->active : true;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('organisations', 'public');
            $data['image'] = $path;
        }

        $organisation = Organisation::create($data);

        return redirect()
            ->route('organisations.show', $organisation)
            ->with('success', 'Organisation créée avec succès.');
    }

    /**
     * Display the specified organisation.
     * Admin, Chef, or Members can view.
     */
    public function show(Organisation $organisation)
    {
        $this->authorize('view', $organisation);

        $user = Auth::user();
        $isAdmin = $user->isAdmin();
        $isChef = $user->isChefOf($organisation->id);
        $canManageMembers = $isAdmin || $isChef;

        $organisation->load('chef', 'members');

        $allUsers = [];
        if ($canManageMembers) {
            $allUsers = User::where('actif', true)->get();
        }

        return view('organisations.show', compact('organisation', 'isAdmin', 'isChef', 'canManageMembers', 'allUsers'));
    }

    /**
     * Update the specified organisation.
     * Admin can update any organisation.
     * Chef can only update their own organisation.
     */
    public function update(Request $request, Organisation $organisation)
    {
        $this->authorize('update', $organisation);

        $user = Auth::user();

        $rules = [
            'nom' => 'required|string|max:255',
            'email_contact' => 'nullable|email|max:255',
            'adresse' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];

        // Only Admin can change the chef
        if ($user->isAdmin()) {
            $rules['chef_organisation_id'] = 'nullable|exists:users,id';
        }

        $request->validate($rules);

        $data = $request->only(['nom', 'email_contact', 'adresse', 'description']);

        // Only Admin can update the chef
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

    /**
     * Toggle the active status of the organisation.
     * Only Admin can activate/deactivate.
     */
    public function toggleActive(Organisation $organisation)
    {
        $this->authorize('toggleActive', $organisation);

        $organisation->update([
            'active' => !$organisation->active
        ]);

        $status = $organisation->active ? 'activée' : 'désactivée';
        return back()->with('success', "Organisation {$status} avec succès.");
    }

    /**
     * Remove the specified organisation.
     * Only Admin can delete organisations.
     */
    public function destroy(Organisation $organisation)
    {
        $this->authorize('delete', $organisation);

        // Delete the image if exists
        if ($organisation->image && !str_starts_with($organisation->image, 'http')) {
            Storage::disk('public')->delete($organisation->image);
        }

        $organisation->delete();

        return redirect()
            ->route('organisations.index')
            ->with('success', 'Organisation supprimée avec succès.');
    }

    /**
     * Redirect to the user's active/default organisation.
     */
    public function myOrganisation()
    {
        $user = Auth::user();
        
        // Check session for active organisation
        $activeId = session('active_organisation_id');
        
        if ($activeId) {
            $organisation = Organisation::find($activeId);
            if ($organisation && $user->can('view', $organisation)) {
                return redirect()->route('organisations.show', $activeId);
            }
        }

        // Find the first organisation the user has access to
        $organisation = $user->chefOfOrganisations()->first() 
            ?? $user->memberOfOrganisations()->first();

        if (!$organisation) {
            abort(404, "Aucune organisation trouvée.");
        }

        return redirect()->route('organisations.show', $organisation->id);
    }

    /**
     * Add a member to the organisation.
     * Only Admin or Chef can add members.
     */
    public function addMember(Request $request, Organisation $organisation)
    {
        $this->authorize('manageMembers', $organisation);

        $request->validate([
            'email' => 'required|email',
            'fonction' => 'nullable|string|max:255',
        ]);

        $user = User::where('email', $request->email)->first();
        $wasCreated = false;
        
        if (!$user) {
            $wasCreated = true;
            $nameParts = explode('@', $request->email);
            $username = $nameParts[0];

            $user = User::create([
                'email' => $request->email,
                'nom' => strtoupper($username),
                'prenom' => ucfirst($username),
                'password' => Hash::make('password'),
                'actif' => true,
                'role_id' => 2, // Default user role
            ]);
        }

        $organisation->members()->syncWithoutDetaching([
            $user->id => ['fonction' => $request->fonction]
        ]);

        $msg = $wasCreated 
            ? "Utilisateur créé et ajouté comme membre." 
            : "Membre ajouté avec succès.";
            
        return back()->with('success', $msg);
    }

    /**
     * Update a member's fonction in the organisation.
     * Only Admin or Chef can update members.
     */
    public function updateMember(Request $request, Organisation $organisation, User $member)
    {
        $this->authorize('manageMembers', $organisation);

        $request->validate([
            'fonction' => 'nullable|string|max:255',
        ]);

        $organisation->members()->updateExistingPivot($member->id, [
            'fonction' => $request->fonction
        ]);

        return back()->with('success', 'Membre mis à jour.');
    }

    /**
     * Remove a member from the organisation.
     * Only Admin or Chef can remove members.
     */
    public function removeMember(Organisation $organisation, User $member)
    {
        $this->authorize('manageMembers', $organisation);

        $organisation->members()->detach($member->id);

        return back()->with('success', 'Membre retiré de l\'organisation.');
    }
}
