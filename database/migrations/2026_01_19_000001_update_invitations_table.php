<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->string('email')->nullable()->after('reunion_id');
            $table->unsignedBigInteger('participant_id')->nullable()->change();
            
            // Re-create unique index to include email or allow nullable participant
            $table->dropUnique(['participant_id', 'reunion_id']);
            // We can't really do a unique on nullable columns in a simple way that covers both, 
            // but we can at least avoid duplicates in business logic.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropColumn('email');
            $table->unsignedBigInteger('participant_id')->nullable(false)->change();
            $table->unique(['participant_id', 'reunion_id']);
        });
    }
};
