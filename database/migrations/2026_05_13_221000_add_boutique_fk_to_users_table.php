<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute la clé étrangère users.boutique_id -> boutiques.id.
     * Placée ici (après la création de la table "boutiques") pour éviter
     * l'erreur MySQL 1824 "Failed to open the referenced table 'boutiques'".
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('boutique_id')
                ->references('id')
                ->on('boutiques')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['boutique_id']);
        });
    }
};
