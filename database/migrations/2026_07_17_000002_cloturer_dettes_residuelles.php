<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Certaines dettes fournisseurs et avances de caisse restent affichées comme
     * « ouvertes » avec un reste dérisoire (ex. 0,16 FCFA) issu d'un résidu de
     * calcul en virgule flottante. Le FCFA n'ayant pas de subdivision utilisée en
     * pratique et le paiement minimal étant de 1, ce reste est impayable et fige
     * la dette. On clôture ces enregistrements dont le reste est inférieur à 1.
     *
     * (Les accesseurs reste_a_payer / reste_a_rembourser arrondissent désormais :
     * ce nettoyage ne concerne que les données déjà en base.)
     */
    public function up(): void
    {
        if (Schema::hasTable('achats') && Schema::hasTable('achat_paiements')) {
            DB::statement("
                UPDATE `achats` a
                LEFT JOIN (
                    SELECT achat_id, SUM(montant) AS paye
                    FROM `achat_paiements`
                    GROUP BY achat_id
                ) ap ON ap.achat_id = a.id
                SET a.statut = 'paye'
                WHERE a.statut = 'dette'
                  AND (a.montant_total - COALESCE(ap.paye, 0)) < 1
            ");
        }

        if (Schema::hasTable('avances_caisse')) {
            DB::statement("
                UPDATE `avances_caisse`
                SET statut = 'remboursee'
                WHERE statut = 'en_cours'
                  AND (montant - montant_rembourse) < 1
            ");
        }
    }

    public function down(): void
    {
        // Rouvrir une dette pour un résidu de quelques centimes n'a pas de sens :
        // aucune action de retour arrière.
    }
};
