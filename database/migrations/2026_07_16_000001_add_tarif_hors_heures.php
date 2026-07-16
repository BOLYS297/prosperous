<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Prix appliqué hors des heures d'ouverture (avant l'ouverture / après la
        // fermeture). Null => on applique le pourcentage global des Paramètres.
        if (! Schema::hasColumn('produits', 'prix_vente_hors_heures')) {
            Schema::table('produits', function (Blueprint $table) {
                $table->decimal('prix_vente_hors_heures', 12, 2)->nullable()->after('prix_vente_grossiste');
            });
        }

        // Marque la vente comme réalisée hors heures (affichage / rapports).
        if (! Schema::hasColumn('ventes', 'hors_heures')) {
            Schema::table('ventes', function (Blueprint $table) {
                $table->boolean('hors_heures')->default(false)->after('mecanicien_id');
            });
        }

        Schema::table('vente_lignes', function (Blueprint $table) {
            // Prix NORMAL de référence, figé : sert à calculer la majoration et
            // la commission mécanicien (qui ne doit PAS porter sur la majoration).
            if (! Schema::hasColumn('vente_lignes', 'prix_unitaire_standard')) {
                $table->decimal('prix_unitaire_standard', 12, 2)->nullable()->after('prix_unitaire');
            }
            // Prime revenant à l'employé = (prix majoré - prix standard) x quantité.
            if (! Schema::hasColumn('vente_lignes', 'prime_employe')) {
                $table->decimal('prime_employe', 12, 2)->nullable()->after('commission_mecanicien');
            }
        });

        // Cumul mensuel des primes hors heures, pour une paie lisible
        // (salaire de base + primes = brut).
        if (! Schema::hasColumn('salary_periods', 'primes')) {
            Schema::table('salary_periods', function (Blueprint $table) {
                $table->decimal('primes', 12, 2)->default(0)->after('gross_salary');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('salary_periods', 'primes')) {
            Schema::table('salary_periods', fn (Blueprint $t) => $t->dropColumn('primes'));
        }
        if (Schema::hasColumn('vente_lignes', 'prime_employe')) {
            Schema::table('vente_lignes', fn (Blueprint $t) => $t->dropColumn('prime_employe'));
        }
        if (Schema::hasColumn('vente_lignes', 'prix_unitaire_standard')) {
            Schema::table('vente_lignes', fn (Blueprint $t) => $t->dropColumn('prix_unitaire_standard'));
        }
        if (Schema::hasColumn('ventes', 'hors_heures')) {
            Schema::table('ventes', fn (Blueprint $t) => $t->dropColumn('hors_heures'));
        }
        if (Schema::hasColumn('produits', 'prix_vente_hors_heures')) {
            Schema::table('produits', fn (Blueprint $t) => $t->dropColumn('prix_vente_hors_heures'));
        }
    }
};
