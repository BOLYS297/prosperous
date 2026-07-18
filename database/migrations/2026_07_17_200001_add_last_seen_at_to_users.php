<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * « Dernière présence » de l'employé : actualisée à chaque requête (throttlée).
     * Sert à détecter un DÉPART ANTICIPÉ lorsque l'employé quitte l'application
     * sans se déconnecter (fermeture du navigateur) : à sa prochaine connexion, on
     * compare cette dernière présence à la fin de sa session pour créer la
     * déduction manquante.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('device_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_seen_at')) {
                $table->dropColumn('last_seen_at');
            }
        });
    }
};
