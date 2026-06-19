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
        Schema::create('horaire_connexions', function (Blueprint $table) {
            $table->id();
            $table->enum('role', ['magasinier', 'boutiquier'])->comment('Rôle concerné par cette tranche horaire');
            $table->integer('jour_semaine')->comment('0=Lundi, 1=Mardi, ..., 6=Dimanche');
            $table->time('heure_debut')->comment('Heure de début de la tranche horaire');
            $table->time('heure_fin')->comment('Heure de fin de la tranche horaire');
            $table->boolean('actif')->default(true)->comment('Si cette tranche est active');
            $table->timestamps();

            // Index pour recherches rapides
            $table->index(['role', 'jour_semaine']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horaire_connexions');
    }
};
