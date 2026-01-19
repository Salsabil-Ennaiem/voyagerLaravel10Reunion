<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            VoyagerDatabaseSeeder::class,
            UsersTableSeeder::class,
            VoyagerDummyDatabaseSeeder::class, // Crucial for Posts/Pages/Categories
            OrganisationsBreadSeeder::class,
            ProjectDataSeeder::class,
        ]);
    }
}
