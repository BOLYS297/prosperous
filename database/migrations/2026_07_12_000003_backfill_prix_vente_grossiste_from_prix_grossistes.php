<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Renseigne le prix grossiste PAR DÉFAUT du produit (produits.prix_vente_grossiste)
     * à partir des tarifs grossistes déjà enregistrés (table prix_grossistes),
     * pour les produits qui n'ont pas encore de prix par défaut.
     * On prend le tarif du grossiste le plus ancien (plus petit id).
     */
    public function up(): void
    {
        if (! Schema::hasColumn('produits', 'prix_vente_grossiste')
            || ! Schema::hasTable('prix_grossistes')) {
            return;
        }

        $rows = DB::table('prix_grossistes')
            ->where('prix_vente', '>', 0)
            ->orderBy('grossiste_id')
            ->get(['produit_id', 'prix_vente']);

        $seen = [];
        foreach ($rows as $row) {
            if (isset($seen[$row->produit_id])) {
                continue;
            }
            $seen[$row->produit_id] = true;

            DB::table('produits')
                ->where('id', $row->produit_id)
                ->whereNull('prix_vente_grossiste')
                ->update(['prix_vente_grossiste' => $row->prix_vente]);
        }
    }

    public function down(): void
    {
        // Backfill de données : pas de rollback.
    }
};
