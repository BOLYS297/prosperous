<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recharges', function (Blueprint $table) {
            // Augmenter la taille de la colonne statut si elle existe
            if (Schema::hasColumn('recharges', 'statut')) {
                $table->string('statut', 100)->change();
            }

            // Ajouter le champ message_probleme pour enregistrer les problèmes signalés
            if (!Schema::hasColumn('recharges', 'message_probleme')) {
                $table->text('message_probleme')->nullable()->after('raison_rejet');
            }
        });
    }

    public function down(): void
    {
        Schema::table('recharges', function (Blueprint $table) {
            if (Schema::hasColumn('recharges', 'message_probleme')) {
                $table->dropColumn('message_probleme');
            }
            // Réduire la taille de la colonne statut à la valeur d'origine
            if (Schema::hasColumn('recharges', 'statut')) {
                $table->string('statut', 50)->change();
            }
        });
    }
};
