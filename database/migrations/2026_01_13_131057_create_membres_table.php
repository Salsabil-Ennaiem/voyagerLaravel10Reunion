<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membres', function (Blueprint $table) {
            $table->id();                           
            $table->string('fonction', 100)->nullable();
            $table->text('description')->nullable();

            $table->foreignId('organisation_id')
                  ->constrained('organisations')
                  ->onDelete('cascade');

            $table->foreignId('compte_id')          // compte = utilisateur
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->timestamps();

            // Empêche un utilisateur d'être ajouté 2× dans la même organisation
            $table->unique(['organisation_id', 'compte_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membres');
    }
};