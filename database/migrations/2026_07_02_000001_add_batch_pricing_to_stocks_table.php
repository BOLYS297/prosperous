<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // Sur MySQL/MariaDB, l'index unique (boutique_id, produit_id) sert d'index
        // support à la clé étrangère boutique_id. On ne peut donc pas le supprimer
        // tant que boutique_id n'a pas SON PROPRE index (sinon erreur 1553).
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            if (! $this->indexExists('stocks', 'stocks_boutique_id_index')) {
                Schema::table('stocks', function (Blueprint $table) {
                    $table->index('boutique_id', 'stocks_boutique_id_index');
                });
            }

            if ($this->indexExists('stocks', 'stocks_boutique_id_produit_id_unique')) {
                Schema::table('stocks', function (Blueprint $table) {
                    $table->dropUnique('stocks_boutique_id_produit_id_unique');
                });
            }
        } else {
            // SQLite / autres : pas de contrainte d'index sur les FK.
            try {
                Schema::table('stocks', function (Blueprint $table) {
                    $table->dropUnique('stocks_boutique_id_produit_id_unique');
                });
            } catch (\Throwable $e) {
                // index déjà absent
            }
        }

        // Ajout des colonnes de tarification par lot (idempotent).
        Schema::table('stocks', function (Blueprint $table) {
            if (! Schema::hasColumn('stocks', 'prix_achat_unitaire')) {
                $table->decimal('prix_achat_unitaire', 12, 2)->nullable()->after('quantite');
            }
            if (! Schema::hasColumn('stocks', 'prix_vente_unitaire')) {
                $table->decimal('prix_vente_unitaire', 12, 2)->nullable()->after('prix_achat_unitaire');
            }
            if (! Schema::hasColumn('stocks', 'source_type')) {
                $table->string('source_type')->nullable()->after('prix_vente_unitaire');
            }
            if (! Schema::hasColumn('stocks', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            foreach (['prix_achat_unitaire', 'prix_vente_unitaire', 'source_type', 'source_id'] as $column) {
                if (Schema::hasColumn('stocks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        $driver = Schema::getConnection()->getDriverName();

        // Restaure l'index unique s'il n'existe pas déjà.
        if (! in_array($driver, ['mysql', 'mariadb'], true) || ! $this->indexExists('stocks', 'stocks_boutique_id_produit_id_unique')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->unique(['boutique_id', 'produit_id']);
            });
        }

        // Retire l'index support temporaire ajouté dans up().
        if (in_array($driver, ['mysql', 'mariadb'], true) && $this->indexExists('stocks', 'stocks_boutique_id_index')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->dropIndex('stocks_boutique_id_index');
            });
        }
    }

    /**
     * Vérifie l'existence d'un index (MySQL/MariaDB) via information_schema.
     */
    private function indexExists(string $table, string $index): bool
    {
        $rows = Schema::getConnection()->select(
            "SELECT COUNT(*) AS aggregate FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?",
            [$table, $index]
        );

        return (int) ($rows[0]->aggregate ?? 0) > 0;
    }
};
