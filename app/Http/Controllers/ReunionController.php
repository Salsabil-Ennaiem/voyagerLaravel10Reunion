<?php

namespace App\Http\Controllers;

use App\Models\Reunion;
use Illuminate\Http\Request;
use App\Notifications\ReunionUpdatedNotification ; 
use Illuminate\Support\Facades\DB;
use App\Models\Invitation ;
use Carbon\Carbon;
use App\Models\User ;
use Illuminate\Support\Facades\Log;

class ReunionController extends Controller
{
    /**
     * Fetch reunions for calendar (JSON)
     */
    public function list(Request $request)
    {
        $start = Carbon::parse($request->start);
        $end = Carbon::parse($request->end);

        $reunionsQuery = Reunion::whereBetween('date_debut', [$start, $end]);

        $user = auth()->user();
        if ($user) {
            $activeOrgId = $user->getActiveOrganisationId();

            if ($user->isAdmin()) {
                // Admin sees all by default. Only filter if organisation_id is provided in the request.
                if ($request->filled('organisation_id')) {
                    $reunionsQuery->where('organisation_id', $request->organisation_id);
                }
            } else {
                // Non-admin logic: MUST have an active organisation or they see nothing.
                if (!$activeOrgId) {
                    $firstOrg = $user->chefOfOrganisations()->first() ?? $user->memberOfOrganisations()->first();
                    if ($firstOrg) {
                        $activeOrgId = $firstOrg->id;
                        session(['active_organisation_id' => $activeOrgId]);
                    }
                }

                if ($activeOrgId) {
                    $reunionsQuery->where('organisation_id', $activeOrgId);
                    
                    // If not chef in this specific org, filter by invitation
                    if (!$user->isChefIn($activeOrgId)) {
                        $reunionsQuery->whereHas('invitations', function($q) use ($user) {
                            $q->where('email', $user->email);
                        });
                    }
                } else {
                    $reunionsQuery->whereRaw('1 = 0');
                }
            }
        }

        $reunions = $reunionsQuery->get()
            ->map(function ($reunion) {
                return [
                    'id' => $reunion->id,
                    'title' => $reunion->objet,
                    'start' => $reunion->date_debut->toDateTimeString(),
                    'end' => $reunion->date_fin->toDateTimeString(),
                    'status' => $reunion->statut, // for color coding
                    'type' => $reunion->type,
                    'description' => $reunion->description,
                    'lieu' => $reunion->lieu,
                ];
            });

        return response()->json($reunions);
    }
    /*
 public function organisations()
    {
        return response()->json(
            \Illuminate\Support\Facades\Cache::remember('organisations_all', 3600, function () {
                return Organisation::all(['id', 'nom']);
            })
        );
    }
        */
/*
    public function getOptions()
    {
        return response()->json([
            'types' => [
                ['id' => 'presentiel', 'label' => 'Présentiel'],
                ['id' => 'visio', 'label' => 'Visio'],
                ['id' => 'hybride', 'label' => 'Hybride']
            ],
            'statuses' => [
                ['id' => 'brouillon', 'label' => 'Brouillon'],
                ['id' => 'planifiee', 'label' => 'Planifiée'],
                ['id' => 'en_cours', 'label' => 'En Cours'],
                ['id' => 'terminee', 'label' => 'Terminée'],
                ['id' => 'annulee', 'label' => 'Annulée']
            ]
        ]);
    }
*/

    public function store(Request $request)
    {
        $request->validate([
            'objet' => 'required|string|max:200',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'statut' => 'required|in:brouillon,planifiee,en_cours,terminee,annulee',
            'type' => 'required|in:presentiel,visio,hybride',
            'participants' => 'nullable|array',
            'participants.*' => 'email',
        ]);

        try {
            $user = auth()->user();
            $userRole = $user->getAttributes()['role'] ?? 'membre';
            $isAdmin =  $user->role_id == 1;

            // Restriction: Seuls l'Admin et le Chef peuvent ajouter des réunions
            if (!$isAdmin && $userRole === 'membre') {
                return response()->json([
                    'success' => false,
                    'message' => 'Les membres ne sont pas autorisés à créer des réunions.'
                ], 403);
                //permission 
            }

            $dateDebut = Carbon::parse($request->date_debut);

            // Restriction: Le chef ne peut pas ajouter une réunion pour une date passée
            if ($userRole === 'chef_organisation' && !$isAdmin && $dateDebut->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Un Chef ne peut pas créer de réunion dans le passé.'
                ], 422);
                //correcte donne mais ne peut pas le traiter 
            }

            // Convert empty strings to null
            $data = $request->only([ 'description', 'lieu', 'type', 'statut']);
            foreach ($data as $key => $value) {
                if ($value === '') {
                    $data[$key] = null;
                }
            }

            // Fallback for organisation_id test 
            $orgId = $request->organisation_id;
            if (!$orgId) {
              
                     return response()->json([
                        'success' => false, 
                        'message' => 'Aucune organisation trouvée.'
                    ], 422);
                }
            $data['organisation_id'] = $orgId;

            $reunion = Reunion::create($data);
            // Gestion des participants
            if ($request->has('participants')) {
                foreach ($request->participants as $email) {
                    $participant = User::where('email', $email)->first();
                    
                    Invitation::create([
                        'reunion_id' => $reunion->id,
                        'participant_id' => $participant ? $participant->id : null,
                        'email' => $email,
                        'statut' => 'en_attente',
                    ]);}
                   $this->notifyParticipants($reunion,'created');
                }
            return response()->json(['success' => true, 'message' => 'Réunion créée (certaines notifications par email peuvent avoir échoué si le serveur est indisponible)', 'data' => $reunion]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getNotifications()
    {
        $user = auth()->user();
        if (!$user) return response()->json([]);

        $notifications = $user->unreadNotifications->map(function($n) {
            return [
                'id' => $n->id,
                'data' => $n->data,
                'created_at' => $n->created_at->diffForHumans(),
            ];
        });

        return response()->json($notifications);
    }

    public function markNotificationAsRead($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        if ($notification->read_at) {
        return response()->json(['message' => 'Already read'], 200);
    }
            $notification->markAsRead();
        return response()->json(['success' => true]);
    }

    /**
     * Update an existing reunion
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'objet' => 'required|string|max:200',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after:date_debut',
            'statut' => 'required|in:brouillon,planifiee,en_cours,terminee,annulee',
            'type' => 'required|in:presentiel,visio,hybride',
        ]);

        try {
            $reunion = Reunion::findOrFail($id);
            $user = auth()->user();
            $isAdmin = $user->role_id == 1;

            // Authorization: Only admin or chef of the organisation can edit
            if (!$isAdmin && !$user->isChefIn($reunion->organisation_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé à modifier cette réunion.'
                ], 403);
            }

           
            // Update the reunion
            $data = $request->only(['objet', 'description', 'date_debut', 'date_fin', 'lieu', 'type', 'statut']);
            foreach ($data as $key => $value) {
                if ($value === '') {
                    $data[$key] = $reunion->$key;
                }
            }
            $reunion->update($data);
            $reunion->refresh();

            // Notify participants about the update
            $this->notifyParticipants($reunion, 'updated');

            return response()->json(['success' => true, 'message' => 'Réunion mise à jour', 'data' => $reunion]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Delete a reunion
     */
    public function destroy($id)
    {
        try {
            $reunion = Reunion::findOrFail($id);
            $user = auth()->user();
            $isAdmin = $user->role_id == 1;

            // Authorization: Only admin or chef of the organisation can delete
            if (!$isAdmin && !$user->isChefIn($reunion->organisation_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé à supprimer cette réunion.'
                ], 403);
            }

            // Notify participants before deletion
            $this->notifyParticipants($reunion, 'deleted');

            // Delete related invitations first
            $reunion->invitations()->delete();
            
            // Delete the reunion
            $reunion->delete();

            return response()->json(['success' => true, 'message' => 'Réunion supprimée']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper to notify all participants of a reunion about updates/deletions
     */
    protected function notifyParticipants(Reunion $reunion, string $action)
    {
        try {
            $notifiedUserIds = [];
            $currentUserId = auth()->id();

            // Notify all invited participants
            foreach ($reunion->invitations as $invitation) {
                if ($invitation->participant_id && $invitation->participant_id !== $currentUserId) {
                    if (!in_array($invitation->participant_id, $notifiedUserIds)) {
                        $participant = User::find($invitation->participant_id);
                        if ($participant) {
                            $participant->notify(new ReunionUpdatedNotification($reunion, $action));
                            $notifiedUserIds[] = $invitation->participant_id;
                        }
                    }
                }
            }

            // Also notify admins
            $admins = User::where('role_id', 1)->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $currentUserId && !in_array($admin->id, $notifiedUserIds)) {
                    $admin->notify(new ReunionUpdatedNotification($reunion, $action));
                    $notifiedUserIds[] = $admin->id;
                }
            }
        } catch (\Exception $e) {
            Log::error("Erreur notification ($action): " . $e->getMessage());
        }
    }
}
