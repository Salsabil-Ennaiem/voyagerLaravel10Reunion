<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utilisateurs', function (Blueprint $table) {
            $table->id();
            $table->string('cin_ou_passeport', 30)->unique()->nullable();
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->string('email')->unique();
            $table->string('telephone', 20)->nullable();
            $table->enum('role', ['admin', 'chef_organisation', 'membre', 'invite'])->default('membre');
            $table->string('password');                    // mdp → password (convention Laravel)
            $table->boolean('actif')->default(true);
            $table->string('image')->nullable();           // chemin ou nom du fichier
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utilisateurs');
    }
};