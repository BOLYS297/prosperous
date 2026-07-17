<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un transfert magasin -> boutique déplaçait le stock SANS porter son coût :
     * la boutique recevait un lot sans prix d'achat (fusionné dans un lot unique).
     * On aligne ce flux sur le transfert boutique <-> boutique, qui fige déjà les
     * prix à l'expédition pour recréer le lot à l'identique à la réception.
     */
    public function up(): void
    {
        Schema::table('demande_transferts', function (Blueprint $table) {
            if (! Schema::hasColumn('demande_transferts', 'prix_achat_unitaire')) {
                $table->decimal('prix_achat_unitaire', 12, 2)->nullable()->after('quantite_recue');
            }
            if (! Schema::hasColumn('demande_transferts', 'prix_vente_unitaire')) {
                $table->decimal('prix_vente_unitaire', 12, 2)->nullable()->after('prix_achat_unitaire');
            }
            if (! Schema::hasColumn('demande_transferts', 'prix_vente_grossiste_unitaire')) {
                $table->decimal('prix_vente_grossiste_unitaire', 12, 2)->nullable()->after('prix_vente_unitaire');
            }
        });
    }

    public function down(): void
    {
        Schema::table('demande_transferts', function (Blueprint $table) {
            foreach (['prix_achat_unitaire', 'prix_vente_unitaire', 'prix_vente_grossiste_unitaire'] as $col) {
                if (Schema::hasColumn('demande_transferts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
