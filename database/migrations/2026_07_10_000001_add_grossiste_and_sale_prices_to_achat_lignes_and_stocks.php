<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('achat_lignes', function (Blueprint $table) {
            // prix_vente (client) : la colonne manquait -> le prix saisi n'était jamais
            // persisté sur la ligne (il ne survivait que via Produit.prix_vente).
            if (! Schema::hasColumn('achat_lignes', 'prix_vente')) {
                $table->decimal('prix_vente', 15, 2)->nullable()->after('prix_unitaire');
            }
            // Nouveau : prix de vente GROSSISTE défini à l'achat (par lot).
            if (! Schema::hasColumn('achat_lignes', 'prix_vente_grossiste')) {
                $table->decimal('prix_vente_grossiste', 15, 2)->nullable()->after('prix_vente');
            }
        });

        Schema::table('stocks', function (Blueprint $table) {
            // Prix de vente grossiste porté par chaque LOT (suit le FIFO comme le prix client).
            if (! Schema::hasColumn('stocks', 'prix_vente_grossiste_unitaire')) {
                $table->decimal('prix_vente_grossiste_unitaire', 12, 2)->nullable()->after('prix_vente_unitaire');
            }
        });
    }

    public function down(): void
    {
        Schema::table('achat_lignes', function (Blueprint $table) {
            foreach (['prix_vente_grossiste', 'prix_vente'] as $column) {
                if (Schema::hasColumn('achat_lignes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('stocks', function (Blueprint $table) {
            if (Schema::hasColumn('stocks', 'prix_vente_grossiste_unitaire')) {
                $table->dropColumn('prix_vente_grossiste_unitaire');
            }
        });
    }
};
