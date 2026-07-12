<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('produits', 'prix_vente_grossiste')) {
            Schema::table('produits', function (Blueprint $table) {
                $table->decimal('prix_vente_grossiste', 12, 2)->nullable()->after('prix_vente');
            });
        }

        // Backfill : prix grossiste par défaut = prix grossiste du lot le plus ancien
        // (défini à l'achat) pour chaque produit qui en a un.
        $lots = DB::table('stocks')
            ->where('quantite', '>', 0)
            ->whereNotNull('prix_vente_grossiste_unitaire')
            ->where('prix_vente_grossiste_unitaire', '>', 0)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['produit_id', 'prix_vente_grossiste_unitaire']);

        $seen = [];
        foreach ($lots as $lot) {
            if (isset($seen[$lot->produit_id])) {
                continue;
            }
            $seen[$lot->produit_id] = true;

            DB::table('produits')
                ->where('id', $lot->produit_id)
                ->whereNull('prix_vente_grossiste')
                ->update(['prix_vente_grossiste' => $lot->prix_vente_grossiste_unitaire]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('produits', 'prix_vente_grossiste')) {
            Schema::table('produits', function (Blueprint $table) {
                $table->dropColumn('prix_vente_grossiste');
            });
        }
    }
};
