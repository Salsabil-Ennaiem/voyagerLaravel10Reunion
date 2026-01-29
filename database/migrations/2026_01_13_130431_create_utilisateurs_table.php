<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            
            $table->string('cin_ou_passeport', 30)->unique()->nullable();
            $table->string('nom', 100);
            $table->string('prenom', 100)->nullable();
            $table->string('telephone', 20)->nullable();
            $table->boolean('actif')->default(true);
            $table->string('image')->nullable();           
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'cin_ou_passeport',
                'nom',
                'prenom',
                'telephone',
                'actif',
                'image',
            ]);
        });
    }
};