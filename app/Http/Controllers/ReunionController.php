<?php

namespace App\Http\Controllers;

use App\Models\Reunion;
use App\Models\Organisation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

        if ($request->filled('organisation_id')) {
            $reunionsQuery->where('organisation_id', $request->organisation_id);
        }

        $reunions = $reunionsQuery->get()
            ->map(function ($reunion) {
                return [
                    'id' => $reunion->id,
                    'title' => $reunion->objet,
                    'start' => $reunion->date_debut->toIso8601String(),
                    'end' => $reunion->date_fin ? $reunion->date_fin->toIso8601String() : null,
                    'status' => $reunion->statut, // for color coding
                    'type' => $reunion->type,
                    'description' => $reunion->description,
                    'lieu' => $reunion->lieu,
                ];
            });

        return response()->json($reunions);
    }

    /**
     * Fetch all organisations (JSON) - Cached for performance
     */
    public function organisations()
    {
        return response()->json(
            \Illuminate\Support\Facades\Cache::remember('organisations_all', 3600, function () {
                return Organisation::all(['id', 'nom']);
            })
        );
    }

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

    public function store(Request $request)
    {
        $request->validate([
            'objet' => 'required|string|max:200',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after:date_debut',
            'statut' => 'required|in:brouillon,planifiee,en_cours,terminee,annulee',
            'type' => 'required|in:presentiel,visio,hybride',
            'participants' => 'nullable|array',
            'participants.*' => 'email',
        ]);

        try {
            $user = auth()->user();
            $userRole = $user->getAttributes()['role'] ?? 'membre';
            $isAdmin = $user->hasRole('admin') || $user->role_id == 1;

            // Restriction: Seuls l'Admin et le Chef peuvent ajouter des réunions
            if (!$isAdmin && $userRole === 'membre') {
                return response()->json([
                    'success' => false,
                    'message' => 'Les membres ne sont pas autorisés à créer des réunions.'
                ], 403);
            }

            $dateDebut = Carbon::parse($request->date_debut);

            // Restriction: Le chef ne peut pas ajouter une réunion pour une date passée
            if ($userRole === 'chef_organisation' && !$isAdmin && $dateDebut->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Un Chef ne peut pas créer de réunion dans le passé.'
                ], 422);
            }

            // Convert empty strings to null
            $data = $request->only(['objet', 'description', 'date_debut', 'date_fin', 'lieu', 'type', 'statut']);
            foreach ($data as $key => $value) {
                if ($value === '') {
                    $data[$key] = null;
                }
            }

            // Fallback for organisation_id
            $orgId = $request->organisation_id;
            if (!$orgId) {
                $firstOrg = Organisation::first();
                if (!$firstOrg) {
                     return response()->json([
                        'success' => false, 
                        'message' => 'Aucune organisation trouvée.'
                    ], 422);
                }
                $orgId = $firstOrg->id; 
            }
            $data['organisation_id'] = $orgId;

            $reunion = Reunion::create($data);

            // Gestion des participants
            if ($request->has('participants')) {
                foreach ($request->participants as $email) {
                    $participant = \App\Models\User::where('email', $email)->first();
                    
                    \App\Models\Invitation::create([
                        'reunion_id' => $reunion->id,
                        'participant_id' => $participant ? $participant->id : null,
                        'email' => $email,
                        'statut' => 'en_attente',
                    ]);

                    // Notification pour les membres seulement (Fail-safe)
                    if ($participant) {
                        try {
                            $participant->notify(new \App\Notifications\ReunionInvitationNotification($reunion));
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error("Erreur notification participant ($email): " . $e->getMessage());
                        }
                    }
                }
            }

            // Notification pour l'admin également (Fail-safe)
            try {
                $admins = \App\Models\User::where('role_id', 1)->get();
                foreach ($admins as $admin) {
                    if ($admin->id !== $user->id) { // Ne pas se notifier soi-même
                        $admin->notify(new \App\Notifications\ReunionInvitationNotification($reunion));
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Erreur notification admin: " . $e->getMessage());
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
        $notification->markAsRead();
        return response()->json(['success' => true]);
    }
}
