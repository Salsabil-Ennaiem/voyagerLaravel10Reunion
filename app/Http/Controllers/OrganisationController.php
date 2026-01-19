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

        if ($user->isChef()) {
            return $this->myOrganisation();
        }

        abort(403);
    }

    public function show(Organisation $organisation)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $organisation->chef_organisation_id !== $user->id) {
            abort(403);
        }
        return view('organisations.show', compact('organisation'));
    }

    public function myOrganisation()
    {
        $user = Auth::user();
        $organisation = $user->managedOrganisation;

        if (!$organisation) {
            // Fallback for demo or if data is missing: create or find first
            $organisation = Organisation::first();
            if (!$organisation) {
                 abort(404, "Aucune organisation trouvée.");
            }
        }

        return view('organisations.show', compact('organisation'));
    }

    public function update(Request $request, Organisation $organisation)
    {
        $user = Auth::user();

        // Security check
        if (!$user->isAdmin() && $organisation->chef_organisation_id !== $user->id) {
            abort(403);
        }

        $request->validate([
            'nom' => 'required|string|max:255',
            'email_contact' => 'nullable|email|max:255',
            'adresse' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->only(['nom', 'email_contact', 'adresse', 'description']);

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
}
