<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropUnique('stocks_boutique_id_produit_id_unique');
            $table->decimal('prix_achat_unitaire', 12, 2)->nullable()->after('quantite');
            $table->decimal('prix_vente_unitaire', 12, 2)->nullable()->after('prix_achat_unitaire');
            $table->string('source_type')->nullable()->after('prix_vente_unitaire');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn(['prix_achat_unitaire', 'prix_vente_unitaire', 'source_type', 'source_id']);
            $table->unique(['boutique_id', 'produit_id']);
        });
    }
};
