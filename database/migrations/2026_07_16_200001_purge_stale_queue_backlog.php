<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aucun worker n'a jamais tourné (supervisord ne lançait que php-fpm et
     * nginx) : les notifications mises en file depuis la mise en service se sont
     * accumulées sans jamais être traitées. Le worker arrive avec ce
     * déploiement ; sans cette purge, il délivrerait d'un coup des semaines
     * d'alertes périmées (« validez cette recharge » pour des actions réglées
     * depuis longtemps).
     *
     * Purge unique du retard accumulé AVANT ce déploiement. Une migration ne
     * s'exécute qu'une fois : les jobs créés ensuite sont traités normalement.
     */
    public function up(): void
    {
        if (! Schema::hasTable('jobs')) {
            return;
        }

        $retard = DB::table('jobs')->where('created_at', '<', now()->timestamp)->delete();

        if ($retard > 0) {
            info("Purge du retard de file d'attente : {$retard} job(s) périmé(s) supprimé(s).");
        }
    }

    public function down(): void
    {
        // Des jobs supprimés ne se reconstituent pas : rien à annuler.
    }
};
