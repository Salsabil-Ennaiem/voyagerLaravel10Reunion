<?php

namespace App\Http\Controllers;

use App\Models\Organisation;
use App\Models\User;
use App\Http\Requests\Organistion\StoreOrganisationRequest;
use App\Http\Requests\Organistion\UpdateOrganisationRequest;
use App\Http\Requests\Organistion\AddMemberRequest;
use App\Http\Requests\Organistion\UpdateMemberRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class OrganisationController extends Controller
{
    /**
     * Check if a function is protected (chef-related functions)
     */
    private function isProtectedFunction($function)
    {
        $protectedFunctions = ['chef', "chef d'organisation", 'gérant', 'gerant'];
        return in_array(strtolower(trim($function)), $protectedFunctions);
    }

    /**
     * Handle image upload and storage
     */
    private function handleImageUpload($request, $organisation = null)
    {
        if (!$request->hasFile('image')) {
            return null;
        }

        // Delete old image if updating
        if ($organisation && $organisation->image && !str_starts_with($organisation->image, 'http')) {
            Storage::disk('public')->delete($organisation->image);
        }

        return $request->file('image')->store('organisations', 'public');
    }

    /**
     * Check if a user is a member of an organization
     */
    private function isMemberOfOrganisation(Organisation $organisation, User $member)
    {
        return $organisation->members()->where('compte_id', $member->id)->exists();
    }

    /**
     * Handle chef assignment changes
     */
    private function handleChefAssignment(Organisation $organisation, $oldChefId, $newChefId)
    {
        // Remove old chef from members if different
        if ($oldChefId && $oldChefId != $newChefId) {
            $organisation->members()->detach($oldChefId);
        }
        
        // Add new chef as member with "Chef d'Organisation" function
        if ($newChefId && $newChefId != $oldChefId) {
            $organisation->members()->syncWithoutDetaching([
                $newChefId => ['fonction' => "Chef d'Organisation"]
            ]);
        }
    }

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
        $users = User::where('actif', true)->get();
        return view('organisations.create', compact('users'));
    }

    /**
     * Store a newly created organisation.
     * Only Admin can create organisations.
     */
    public function store(StoreOrganisationRequest $request)
    {
        $this->authorize('create', Organisation::class);
        $data = $request->only(['nom', 'email_contact', 'adresse', 'description', 'chef_organisation_id']);
        $data['active'] = $request->has('active') ? $request->active : true;

        // Handle image upload
        $imagePath = $this->handleImageUpload($request);
        if ($imagePath) {
            $data['image'] = $imagePath;
        }

        $organisation = Organisation::create($data);

        // Automatically add the chef as the first member with "Chef d'Organisation" function
        $chef = User::find($data['chef_organisation_id']);
        if ($chef) {
            $organisation->members()->attach($chef->id, [
                'fonction' => "Chef d'Organisation"
            ]);
        }

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
    public function update(UpdateOrganisationRequest $request, Organisation $organisation)
    {
        $this->authorize('update', $organisation);

        $user = Auth::user();
        $data = $request->only(['nom', 'email_contact', 'adresse', 'description']);

        // Only Admin can update the chef
        if ($user->isAdmin() && $request->has('chef_organisation_id')) {
            $oldChefId = $organisation->chef_organisation_id;
            $newChefId = $request->chef_organisation_id;
            
            $this->handleChefAssignment($organisation, $oldChefId, $newChefId);
            $data['chef_organisation_id'] = $newChefId;
        }

        // Handle image upload
        $imagePath = $this->handleImageUpload($request, $organisation);
        if ($imagePath) {
            $data['image'] = $imagePath;
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

        // Delete all members first
        $organisation->members()->detach();

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
    public function addMember(AddMemberRequest $request, Organisation $organisation)
    {
        $this->authorize('manageMembers', $organisation);
        $user = User::where('email', $request->email)->first();
        $wasCreated = false;
        
        if (!$user) {
            $wasCreated = true;
            $nameParts = explode('@', $request->email);
            $username = $nameParts[0];

            $user = User::create([
                'email' => $request->email,
                'nom' => ucfirst($username),
                'prenom' => ucfirst($username),
                'password' => Hash::make('password'),
                'actif' => true,
                'role_id' => 2, // Default user role
            ]);
        }

        // Check if user is already a member of this organization
        if ($this->isMemberOfOrganisation($organisation, $user)) {
            return back()->with('error', 'Cet utilisateur est déjà membre de cette organisation.');
        }

        // Block chef functions completely in member management
        if ($this->isProtectedFunction($request->fonction)) {
            return back()->with('error', 'Les fonctions de type chef doivent être gérées via la modification de l\'organisation.');
        }

        // Attach the member to the organization with their function
        $organisation->members()->attach($user->id, [
            'fonction' => $request->fonction
        ]);

        $msg = $wasCreated 
            ? "Utilisateur créé et ajouté comme membre avec succès." 
            : "Membre ajouté à l'organisation avec succès.";
            
        return back()->with('success', $msg);
    }

    /**
     * Update a member's fonction in the organisation.
     * Only Admin or Chef can update members.
     */
    public function updateMember(UpdateMemberRequest $request, Organisation $organisation, User $member)
    {
        $this->authorize('manageMembers', $organisation);
        
        // Check if member exists
        if (!$this->isMemberOfOrganisation($organisation, $member)) {
            return back()->with('error', 'Ce membre ne fait pas partie de cette organisation.');
        }
        
        // Block chef functions completely in member management
        if ($this->isProtectedFunction($request->fonction)) {
            return back()->with('error', 'Les fonctions de type chef doivent être gérées via la modification de l\'organisation.');
        }
        
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
        
        // Check if member exists
        if (!$this->isMemberOfOrganisation($organisation, $member)) {
            return back()->with('error', 'Ce membre ne fait pas partie de cette organisation.');
        }
        
        // Prevent removing the chef unless admin is changing the chef
        $currentMember = $organisation->members()->where('compte_id', $member->id)->first();
        
        if ($currentMember && $this->isProtectedFunction($currentMember->pivot->fonction) && !Auth::user()->isAdmin()) {
            return back()->with('error', 'Seul un administrateur peut retirer un chef de l\'organisation.');
        }
        
        $organisation->members()->detach($member->id);

        return back()->with('success', 'Membre retiré de l\'organisation.');
    }
}
