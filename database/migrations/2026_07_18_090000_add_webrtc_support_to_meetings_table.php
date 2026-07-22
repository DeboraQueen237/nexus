<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            // Supprimer l'ancienne colonne platform
            $table->dropColumn('platform');
        });

        Schema::table('meetings', function (Blueprint $table) {
            // Recréer la colonne avec la nouvelle valeur ENUM
            $table->enum('platform', ['daily', 'jitsi', 'twilio', 'webrtc'])
                  ->default('webrtc')
                  ->after('allow_link_join');
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn('platform');
        });

        Schema::table('meetings', function (Blueprint $table) {
            $table->enum('platform', ['daily', 'jitsi', 'twilio'])
                  ->default('daily')
                  ->after('allow_link_join');
        });
    }
};