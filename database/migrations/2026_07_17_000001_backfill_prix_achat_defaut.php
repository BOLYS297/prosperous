<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Les stocks d'inventaire (importés avant le suivi des coûts) et les lots
     * créés sans prix d'achat ont un prix_achat_unitaire NULL. À la vente, le
     * coût était alors gelé à NULL : ces lignes étaient exclues du rapport de
     * bénéfices (« ligne de vente sans coût d'achat ») et ne créditaient aucune
     * commission mécanicien.
     *
     * Or chaque produit porte un PRIX D'ACHAT PAR DÉFAUT. On l'utilise pour
     * renseigner rétroactivement :
     *   1. les lots de stock sans coût ;
     *   2. les lignes de vente déjà enregistrées sans coût.
     *
     * On n'écrase JAMAIS un coût déjà renseigné, et on ignore les produits sans
     * prix d'achat défini (il n'en existe pas ici, mais le garde-fou évite
     * d'inscrire un coût nul).
     */
    public function up(): void
    {
        if (Schema::hasTable('stocks') && Schema::hasColumn('stocks', 'prix_achat_unitaire')) {
            DB::statement('
                UPDATE `stocks` s
                JOIN `produits` p ON p.id = s.produit_id
                SET s.prix_achat_unitaire = p.prix_achat
                WHERE s.prix_achat_unitaire IS NULL
                  AND p.prix_achat IS NOT NULL
                  AND p.prix_achat > 0
            ');
        }

        if (Schema::hasTable('vente_lignes') && Schema::hasColumn('vente_lignes', 'prix_achat_unitaire')) {
            DB::statement('
                UPDATE `vente_lignes` vl
                JOIN `produits` p ON p.id = vl.produit_id
                SET vl.prix_achat_unitaire = p.prix_achat
                WHERE vl.prix_achat_unitaire IS NULL
                  AND p.prix_achat IS NOT NULL
                  AND p.prix_achat > 0
            ');
        }
    }

    public function down(): void
    {
        // Irréversible : on ne sait plus quels coûts étaient NULL avant le
        // backfill. Aucune action de retour arrière.
    }
};
