<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Organisation;
use App\Models\Reunion;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ProjectDataSeeder extends Seeder
{
    public function run()
    {
        // 1. Seed Users
        // Note: 'name' column is missing from users table, using 'nom' and 'prenom' instead.
        $admin = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'nom' => 'Admin',
                'prenom' => 'User',
                'password' => Hash::make('password'),
                'role_id' => 1, // Voyager Admin
                'role' => 'admin',
                'actif' => true,
            ]
        );

        $chef1 = User::firstOrCreate(
            ['email' => 'chef1@test.com'],
            [
                'nom' => 'Chef',
                'prenom' => 'Technique',
                'password' => Hash::make('password'),
                'role_id' => 2, // Voyager User
                'role' => 'chef_organisation',
                'actif' => true,
            ]
        );

        $chef2 = User::firstOrCreate(
            ['email' => 'chef2@test.com'],
            [
                'nom' => 'Responsable',
                'prenom' => 'RH',
                'password' => Hash::make('password'),
                'role_id' => 2,
                'role' => 'chef_organisation',
                'actif' => true,
            ]
        );

        $membre = User::firstOrCreate(
            ['email' => 'membre@test.com'],
            [
                'nom' => 'Membre',
                'prenom' => 'Un',
                'password' => Hash::make('password'),
                'role_id' => 2,
                'role' => 'membre',
                'actif' => true,
            ]
        );

        // 2. Seed Organisations
        $org1 = Organisation::updateOrCreate(
            ['nom' => 'Direction Technique'],
            [
                'description' => 'Département en charge du développement et de l\'infrastructure.',
                'email_contact' => 'tech@entreprise.com',
                'adresse' => '123 Rue de la Tech',
                'chef_organisation_id' => $chef1->id,
            ]
        );

        $org2 = Organisation::updateOrCreate(
            ['nom' => 'Ressources Humaines'],
            [
                'description' => 'Département en charge du personnel et du recrutement.',
                'email_contact' => 'rh@entreprise.com',
                'adresse' => '456 Avenue des Talents',
                'chef_organisation_id' => $chef2->id,
            ]
        );

        // 3. Seed Reunions 
        // We delete existing ones to have a clean set of varied examples
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        Reunion::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        $reunions = [
            [
                'objet' => 'Sprint Planning #42',
                'description' => 'Planning du sprint de fin de mois.',
                'ordre_du_jour' => "1. Revue du backlog\n2. Estimation des tâches\n3. Assignation des tickets",
                'date_debut' => Carbon::now()->addDays(2)->setTime(9, 0, 0),
                'date_fin' => Carbon::now()->addDays(2)->setTime(11, 0, 0),
                'lieu' => 'Salle de réunion A (3ème étage)',
                'type' => 'presentiel',
                'statut' => 'planifiee',
                'organisation_id' => $org1->id,
            ],
            [
                'objet' => 'Daily Standup - Tech',
                'description' => 'Point quotidien rapide sur les blocages.',
                'date_debut' => Carbon::now()->setTime(10, 0, 0),
                'date_fin' => Carbon::now()->setTime(10, 15, 0),
                'lieu' => 'https://meet.google.com/abc-defg-hij',
                'type' => 'visio',
                'statut' => 'en_cours',
                'organisation_id' => $org1->id,
            ],
            [
                'objet' => 'Entretien Recrutement Senior Dev',
                'description' => 'Entretien technique final pour le candidat J. Doe.',
                'date_debut' => Carbon::now()->subDays(1)->setTime(14, 0, 0),
                'date_fin' => Carbon::now()->subDays(1)->setTime(15, 30, 0),
                'lieu' => 'Salle RH 1 / Teams',
                'type' => 'hybride',
                'statut' => 'terminee',
                'organisation_id' => $org2->id,
            ],
            [
                'objet' => 'Team Building Restaurant',
                'description' => 'Déjeuner d\'équipe RH.',
                'date_debut' => Carbon::now()->addDays(10)->setTime(12, 0, 0),
                'date_fin' => Carbon::now()->addDays(10)->setTime(14, 0, 0),
                'lieu' => 'Restaurant Chez Gustave',
                'type' => 'presentiel',
                'statut' => 'annulee',
                'organisation_id' => $org2->id,
            ],
            [
                'objet' => 'Brainstorming Nouveau Produit',
                'description' => 'Session de créativité pour le projet X.',
                'date_debut' => Carbon::now()->addWeeks(1)->setTime(15, 0, 0),
                'date_fin' => Carbon::now()->addWeeks(1)->setTime(17, 0, 0),
                'type' => 'hybride',
                'statut' => 'brouillon',
                'organisation_id' => $org1->id,
            ],
        ];

        foreach ($reunions as $reunionData) {
            Reunion::create($reunionData);
        }
    }
}
