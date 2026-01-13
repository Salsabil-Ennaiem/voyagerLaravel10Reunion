<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();                           // id_Inv
            
            $table->foreignId('participant_id')     // id_participant → utilisateur
                  ->constrained('utilisateurs')
                  ->onDelete('cascade');
                  
            $table->foreignId('reunion_id')
                  ->constrained('reunions')
                  ->onDelete('cascade');

            $table->enum('statut', ['en_attente', 'accepte', 'refuse', 'peut-etre'])->default('en_attente');
            $table->text('commentaire')->nullable();
            $table->dateTime('date_presence')->nullable();   // date réelle de présence
            $table->integer('note')->nullable()->unsigned()->check('note >= 0 AND note <= 5');

            $table->timestamps();

            $table->unique(['participant_id', 'reunion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};