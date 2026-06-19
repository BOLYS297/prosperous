<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('achats', function (Blueprint $table) {
            // debit_boutique_id : utilisé UNIQUEMENT pour les achats payés comptant (une boutique débite son solde)
            // Pour les dettes (statut='dette'), ce champ reste NULL car TOUTES les boutiques doivent la régler
            $table->foreignId('debit_boutique_id')->nullable()->after('boutique_id')->constrained('boutiques')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('achats', function (Blueprint $table) {
            $table->dropForeign(['debit_boutique_id']);
            $table->dropColumn('debit_boutique_id');
        });
    }
};
