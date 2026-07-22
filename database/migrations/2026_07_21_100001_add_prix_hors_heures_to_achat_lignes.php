<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Une ligne d'achat peut désormais définir le « prix de vente en heures
     * supplémentaires » du produit, comme elle définit déjà son prix de vente et
     * son prix grossiste : rempli, il met à jour la fiche produit ; laissé vide,
     * la valeur actuelle du produit reste inchangée.
     */
    public function up(): void
    {
        Schema::table('achat_lignes', function (Blueprint $table) {
            if (! Schema::hasColumn('achat_lignes', 'prix_vente_hors_heures')) {
                $table->decimal('prix_vente_hors_heures', 15, 2)->nullable()->after('prix_vente_grossiste');
            }
        });
    }

    public function down(): void
    {
        Schema::table('achat_lignes', function (Blueprint $table) {
            if (Schema::hasColumn('achat_lignes', 'prix_vente_hors_heures')) {
                $table->dropColumn('prix_vente_hors_heures');
            }
        });
    }
};
