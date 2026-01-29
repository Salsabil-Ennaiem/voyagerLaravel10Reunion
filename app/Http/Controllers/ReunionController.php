<?php

namespace App\Http\Controllers;

use App\Models\Reunion;
use App\Models\Organisation; 
use App\Models\User;
use App\Models\Invitation;
use App\Http\Requests\StoreReunionRequest;
use App\Http\Requests\UpdateReunionRequest;
use App\Notifications\ReunionUpdatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache; 

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class ReunionController extends Controller
{
    public function __construct()
    {
        // Automatically authorize resource actions based on ReunionPolicy
        // Maps: index->viewAny, store->create, show->view, update->update, destroy->delete
        // Because of custom routes naming ('list'), we handle 'viewAny' manually in list()
        // But store, update, destroy are covered if we use correct route params.
        $this->authorizeResource(Reunion::class, 'reunion');
    }

    /**
     * Export reunions to PDF or Excel
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Reunion::class);

        $orgId = $request->input('organisation_id');
        $format = $request->input('format', 'pdf');

        $query = Reunion::with('organisation', 'invitations');

        $user = auth()->user();
        if ($user) {
            if ($user->isAdmin()) {
                if ($orgId) { 
                    $query->where('organisation_id', $orgId); 
                }
            } else {
                
                $activeOrgId = $orgId ?: $user->getActiveOrganisationId();
                if ($activeOrgId) {
                    $query->where('organisation_id', $activeOrgId);
                    
                    if (!$user->isChefIn($activeOrgId)) {
                        $query->whereHas('invitations', function($q) use ($user) {
                            $q->where('email', $user->email);
                        });
                    }
                } else {
                    // If no active org selected, show all reunions from all their orgs
                    $chefOrgIds = $user->chefOfOrganisations()->pluck('id')->toArray();
                    $memberOrgIds = $user->memberOfOrganisations()->pluck('organisation_id')->toArray();
                    
                    $query->where(function($q) use ($user, $chefOrgIds, $memberOrgIds) {
                        if (!empty($chefOrgIds)) {
                            $q->orWhereIn('organisation_id', $chefOrgIds);
                        }
                        if (!empty($memberOrgIds)) {
                            $q->orWhere(function($sub) use ($user, $memberOrgIds) {
                                $sub->whereIn('organisation_id', $memberOrgIds)
                                    ->whereHas('invitations', function($inv) use ($user) {
                                        $inv->where('email', $user->email);
                                    });
                            });
                        }
                        
                        // If they have no orgs at all, they see nothing
                        if (empty($chefOrgIds) && empty($memberOrgIds)) {
                            $q->whereRaw('1 = 0');
                        }
                    });
                }
            }
        }

        $reunions = $query->orderBy('date_debut', 'desc')->get();

        if ($format === 'excel') {
            return $this->exportToExcel($reunions);
        }

        try {
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $title = "Rapport Global des Réunions";
                if ($orgId) {
                    $org = Organisation::find($orgId);
                    if ($org) { $title .= " - " . $org->nom; }
                }

                $data = [
                    'reunions' => $reunions,
                    'title' => $title,
                    'date' => now()->format('d/m/Y H:i')
                ];
                
                $pdf = Pdf::loadView('exports.reunions_pdf', $data);
                
                $filename = "reunions_export_" . now()->format('Ymd_His') . ".pdf";
                
                return response()->streamDownload(function () use ($pdf) {
                    echo $pdf->output();
                }, $filename, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => "attachment; filename=\"$filename\"",
                    'Cache-Control' => 'no-cache, must-revalidate',
                    'Pragma' => 'no-cache',
                    'Expires' => '0',
                    'X-Content-Type-Options' => 'nosniff'
                ]);
            }
        } catch (\Exception $e) {
            Log::error("PDF Export failed: " . $e->getMessage());
        }

        // Fallback to print-friendly HTML
        return view('exports.reunions_pdf', [
            'reunions' => $reunions,
            'title' => "Rapport Global des Réunions",
            'date' => now()->format('d/m/Y H:i'),
            'print' => true
        ]);
    }

    protected function exportToExcel($reunions)
    {
        $filename = "reunions_export_" . now()->format('Ymd_His') . ".csv";
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'Objet', 
            'Description', 
            'Date Début', 
            'Date Fin', 
            'Lieu', 
            'Type de Réunion', 
            'Statut', 
            'Nombre de Participants',
            'Organisation'
        ];

        $callback = function() use($reunions, $columns) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel compatibility
            
            // Write headers
            fputcsv($file, $columns, ',');

            foreach ($reunions as $reunion) {
                fputcsv($file, [
                    $reunion->objet,
                    $reunion->description ?: 'N/A',
                    $reunion->date_debut->format('d/m/Y H:i'),
                    $reunion->date_fin->format('d/m/Y H:i'),
                    $reunion->lieu ?: 'N/A',
                    ucfirst($reunion->type),
                    strtoupper(str_replace('_', ' ', $reunion->statut)),
                    $reunion->invitations->count(),
                    $reunion->organisation->nom ?? 'N/A'
                ], ',');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }


    /**
     * Fetch reunions for calendar (JSON)
     */
    public function list(Request $request)
    {
        $this->authorize('viewAny', Reunion::class);

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

        $reunions = $reunionsQuery->with('invitations')->get()
            ->map(function ($reunion) use ($user) {
                return [
                    'id' => $reunion->id,
                    'title' => $reunion->objet,
                    'start' => $reunion->date_debut->toDateTimeString(),
                    'end' => $reunion->date_fin->toDateTimeString(),
                    'status' => $reunion->statut, // for color coding
                    'type' => $reunion->type,
                    'description' => $reunion->description,
                    'ordre_du_jour' => $reunion->ordre_du_jour,
                    'lieu' => $reunion->lieu,
                    'organisation_id' => $reunion->organisation_id, // Useful for frontend info
                    'participants' => $reunion->invitations->pluck('email')->toArray(),
                    'can_edit' => $user->can('update', $reunion),
                    'can_delete' => $user->can('delete', $reunion),
                ];
            });

        return response()->json($reunions);
    }

    public function getOptions()
    {
        $options = [
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
        ];

        // If admin, send organisations list
        $user = auth()->user();
        if ($user && $user->isAdmin()) {
            $options['organisations'] = Organisation::select('id', 'nom', 'code')->orderBy('nom')->get();
        }

        return response()->json($options);
    }

    public function store(StoreReunionRequest $request)
    {
        // Validation and Authorization handled by Request and Policy
        
        try {
            $data = $request->validated();
            
            // Unset participants from data to save Reunion model (they are saved separately)
            $participants = $data['participants'] ?? [];
            unset($data['participants']);

            // organisation_id is already in $data from StoreReunionRequest validation logic (merged)
            
            $reunion = Reunion::create($data);

            // Gestion des participants
            foreach ($participants as $email) {
                $participant = User::where('email', $email)->first();
                
                Invitation::create([
                    'reunion_id' => $reunion->id,
                    'participant_id' => $participant ? $participant->id : null,
                    'email' => $email,
                    'statut' => 'en_attente',
                ]);
            }
            
            $this->notifyParticipants($reunion, 'created');
            
            return response()->json([
                'success' => true, 
                'message' => 'Réunion créée avec succès', 
                'data' => $reunion
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur création réunion: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Erreur serveur: ' . $e->getMessage()
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
     * Show the form for editing the specified reunion (returns JSON for AJAX).
     */
    public function edit(Reunion $reunion)
    {
        $this->authorize('view', $reunion);
        
        // Load relationships needed for the form
        $reunion->load(['invitations', 'organisation']);
        
        return response()->json($reunion);
    }

    /**
     * Update an existing reunion
     * Route Model Binding provides $reunion
     */
    public function update(UpdateReunionRequest $request, Reunion $reunion)
    {
        // Auth/Validation passed
        try {
            $data = $request->validated();
            
            $participants = $data['participants'] ?? [];
            unset($data['participants']);
            
            $reunion->update($data);

            // Sync participants
            // Remove existing ones
            $reunion->invitations()->delete();
            
            // Add new ones
            foreach ($participants as $email) {
                $participant = User::where('email', $email)->first();
                Invitation::create([
                    'reunion_id' => $reunion->id,
                    'participant_id' => $participant ? $participant->id : null,
                    'email' => $email,
                    'statut' => 'en_attente',
                ]);
            }

            $reunion->refresh();
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
    public function destroy(Reunion $reunion)
    {
        try {
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
            // Eager load invitations if possible, but lazy load fine here
            foreach ($reunion->invitations as $invitation) {
                // If invite has user ID attached
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
