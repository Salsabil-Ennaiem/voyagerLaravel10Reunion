<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisations', function (Blueprint $table) {
            $table->id();                           // id_org
            $table->string('nom', 150);
            $table->text('description')->nullable();
            $table->string('email_contact')->nullable();
            $table->string('adresse', 255)->nullable();
            $table->foreignId('chef_organisation_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisations');
    }
};