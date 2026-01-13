<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reunions', function (Blueprint $table) {
            $table->id();                           // id_Réunion
            $table->string('objet', 200);
            $table->text('description')->nullable();
            $table->text('ordre_du_jour')->nullable();   // ordre_jour → ordre_du_jour
            $table->dateTime('date_debut');
            $table->dateTime('date_fin')->nullable();
            $table->string('lieu', 150)->nullable();
            $table->enum('type', ['presentiel', 'visio', 'hybride'])->default('presentiel');
            $table->enum('statut', ['brouillon', 'planifiee', 'en_cours', 'terminee', 'annulee'])
                  ->default('brouillon');
            
            $table->foreignId('organisation_id')
                  ->constrained('organisations')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reunions');
    }
};