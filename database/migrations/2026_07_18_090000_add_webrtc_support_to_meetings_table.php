<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL ne permet pas d'ajouter une valeur à un ENUM via Blueprint
        // directement : on passe par une instruction SQL brute.
        DB::statement("ALTER TABLE meetings MODIFY COLUMN platform ENUM('daily','jitsi','twilio','webrtc') NOT NULL DEFAULT 'webrtc'");

        Schema::table('meetings', function (Blueprint $table) {
            // Jeton opaque utilisé dans le lien d'invitation partageable
            // (/meetings/join/{invite_token}), pour ne pas exposer les ID
            // séquentiels des réunions.
            $table->string('invite_token', 40)->nullable()->unique()->after('meeting_url');

            // Autorise n'importe quel utilisateur authentifié de la
            // plateforme possédant le lien à rejoindre, même s'il n'a pas
            // été explicitement invité au préalable (comme Zoom/Meet).
            $table->boolean('allow_link_join')->default(true)->after('invite_token');
        });

        // Génère un jeton pour les réunions déjà existantes.
        DB::table('meetings')->whereNull('invite_token')->orderBy('id')->each(function ($meeting) {
            DB::table('meetings')->where('id', $meeting->id)->update([
                'invite_token' => Str::random(32),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['invite_token', 'allow_link_join']);
        });

        DB::statement("ALTER TABLE meetings MODIFY COLUMN platform ENUM('daily','jitsi','twilio') NOT NULL DEFAULT 'daily'");
    }
};
