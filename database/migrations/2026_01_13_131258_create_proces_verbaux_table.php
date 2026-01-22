<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proces_verbaux', function (Blueprint $table) {
            $table->id();                           // Id_PV
            
            $table->foreignId('reunion_id')
                  ->constrained('reunions')
                  ->onDelete('cascade');

            $table->unsignedTinyInteger('version')->default(1);
            $table->text('discussion')->nullable();
            $table->text('recommandations')->nullable();
            $table->text('solutions')->nullable();
            $table->dateTime('signe_at')->nullable();
            $table->enum('statut', ['brouillon', 'valide', 'approver' ,'signer'])->default('brouillon');
            $table->string('doc_hash', 64)->nullable();     // SHA-256 ou autre hash du document

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proces_verbaux');
    }
};