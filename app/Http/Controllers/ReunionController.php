<?php

namespace App\Http\Controllers;

use App\Models\Reunion;
use App\Models\Organisation; 
use App\Models\User;
use App\Models\Invitation;
use App\Http\Requests\Reunion\StoreReunionRequest;
use App\Http\Requests\Reunion\UpdateReunionRequest;
use App\Notifications\ReunionUpdatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache; 
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\HolidayService;
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
        $query = $this->applyUserOrganizationFilter($query, auth()->user(), $orgId);

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
        $reunionsQuery = $this->applyUserOrganizationFilter($reunionsQuery, auth()->user(), $request->input('organisation_id'));

        $reunions = $reunionsQuery->with('invitations')->get()
            ->map(function ($reunion) {
                $user = auth()->user();
                return [
                    'id' => $reunion->id,
                    'title' => $reunion->objet,
                    'start' => $reunion->date_debut->toDateTimeString(),
                    'end' => $reunion->date_fin->toDateTimeString(),
                    'status' => $reunion->statut,
                    'type' => $reunion->type,
                    'description' => $reunion->description,
                    'ordre_du_jour' => $reunion->ordre_du_jour,
                    'lieu' => $reunion->lieu,
                    'organisation_id' => $reunion->organisation_id,
                    'participants' => $reunion->invitations->pluck('email')->toArray(),
                    'can_edit' => $user->can('update', $reunion),
                    'can_delete' => $user->can('delete', $reunion),
                ];
            });

        // Include holidays for the current year
        $holidays = HolidayService::getHolidaysForYear($start->year);
        
        $user = auth()->user();

        return response()->json([
            'events' => $reunions,
            'holidays' => $holidays,
            'organisations' => $user ? $this->getUserOrganisations($user) : []
        ]);
    }

    public function getOptions()
    {
        $user = auth()->user();
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
            ],
            'user_info' => [
                'is_admin' => $user ? $user->isAdmin() : false,
                'is_chef' => $user ? $user->isChef() : false,
                'can_create' => $user ? $user->can('create', Reunion::class) : false,
                'min_date' => $this->getMinDateForUser($user),
                'default_type' => 'presentiel',
                'default_status' => 'planifiee'
            ]
        ];

        // If admin, send organisations list
        if ($user && $user->isAdmin()) {
            $options['organisations'] = $this->getUserOrganisations($user);
        } elseif ($user) {
            // Non-admin users get only organizations they can create reunions for
            $options['organisations'] = $this->getUserOrganisations($user);
        }

        return response()->json($options);
    }

    /**
     * Get organizations accessible to the current user for creating reunions
     */
    private function getUserOrganisations($user)
    {
        if (!$user) {
            return collect();
        }

        if ($user->isAdmin()) {
            return Organisation::select('id', 'nom', 'code')->orderBy('nom')->get();
        }

        // Non-admin users get only organizations they can create reunions for
        $organisations = collect();
        
        // Get organizations where user is chef
        $chefOrgs = $user->chefOfOrganisations()->select('id', 'nom', 'code')->get();
        
        // Check each organization if user can create reunions there
        foreach ($chefOrgs as $org) {
            if ($user->can('createForOrganisation', [Reunion::class, $org->id])) {
                $organisations->push($org);
            }
        }
        
        return $organisations;
    }

    private function getMinDateForUser($user): ?string
    {
        if (!$user) return null;
        
        // Chefs cannot create meetings in the past
        if ($user->isChef() && !$user->isAdmin()) {
            return now()->format('Y-m-d\TH:i');
        }
        
        return null; // Admins can create any date
    }

    public function getOrganisations()
    {
        $user = auth()->user();
        $organisations = $this->getUserOrganisations($user);
        return response()->json($organisations);
    }

    public function store(StoreReunionRequest $request)
    {
        // Additional authorization check using policy
        $this->authorize('create', Reunion::class);
        
        // Validation and Authorization handled by Request and Policy
        
        try {
            $data = $request->validated();
            
            // Unset participants from data to save Reunion model (they are saved separately)
            $participants = $data['participants'] ?? [];
            unset($data['participants']);

            // organisation_id is already in $data from StoreReunionRequest validation logic (merged)
            
            $reunion = Reunion::create($data);

            // Gestion des participants
            $this->createParticipantInvitations($reunion, $participants);
            
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
        try {
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
        } catch (\Exception $e) {
            Log::error('Error fetching notifications: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch notifications',
                'message' => $e->getMessage()
            ], 500);
        }
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
            $this->createParticipantInvitations($reunion, $participants);
            
            $this->notifyParticipants($reunion, 'updated');
            
            return response()->json(['success' => true, 'message' => 'Réunion mise à jour', 'data' => $reunion]);
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour réunion: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false, 
                'message' => 'Erreur serveur: ' . $e->getMessage()
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
    /**
     * Create participant invitations for a reunion
     */
    private function createParticipantInvitations(Reunion $reunion, array $participants)
    {
        foreach ($participants as $email) {
            $participant = User::where('email', $email)->first();
            
            Invitation::create([
                'reunion_id' => $reunion->id,
                'participant_id' => $participant ? $participant->id : null,
                'email' => $email,
                'statut' => 'en_attente',
            ]);
        }
    }

    /**
     * Apply user-specific organization filtering to reunion queries
     */
    private function applyUserOrganizationFilter($query, $user, $orgId = null)
    {
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin()) {
            // Admin sees all reunions in the database
            // Only filter if organisation_id is explicitly provided
            if ($orgId) { 
                $query->where('organisation_id', $orgId); 
            }
        } else {
            // Non-admin users: get all their organizations and filter accordingly
            $chefOrgIds = $user->chefOfOrganisations()->pluck('id')->toArray();
            $memberOrgIds = $user->memberOfOrganisations()->pluck('organisation_id')->toArray();
            
            if (empty($chefOrgIds) && empty($memberOrgIds)) {
                // User has no organizations, show nothing
                $query->whereRaw('1 = 0');
            } else {
                $query->where(function($query) use ($user, $chefOrgIds, $memberOrgIds) {
                    // If user is chef of organizations, show all reunions from those orgs
                    if (!empty($chefOrgIds)) {
                        $query->orWhereIn('organisation_id', $chefOrgIds);
                    }
                    
                    // If user is member of organizations, show only reunions they're invited to
                    if (!empty($memberOrgIds)) {
                        $query->orWhere(function($subQuery) use ($user, $memberOrgIds) {
                            $subQuery->whereIn('organisation_id', $memberOrgIds)
                                     ->whereHas('invitations', function($invitationQuery) use ($user) {
                                         $invitationQuery->where('email', $user->email);
                                     });
                        });
                    }
                });
                
                // If specific org is requested, ensure user has access to it
                if ($orgId) {
                    $query->where('organisation_id', $orgId);
                }
            }
        }

        return $query;
    }

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
