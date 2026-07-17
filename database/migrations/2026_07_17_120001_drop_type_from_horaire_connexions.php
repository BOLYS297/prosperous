<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Le type de tranche (normale / majorée) devient obsolète.
     *
     * Nouveau modèle : une tranche est simplement la SESSION PRINCIPALE de
     * l'employé. Les heures supplémentaires (et leur majoration) sont désormais
     * déduites automatiquement du fait de travailler EN DEHORS de cette session
     * (avant le début ou après la fin), sans qu'aucune tranche ne soit marquée
     * « majorée ». La colonne n'a donc plus d'usage.
     */
    public function up(): void
    {
        if (Schema::hasColumn('horaire_connexions', 'type')) {
            DB::statement('ALTER TABLE `horaire_connexions` DROP COLUMN `type`');
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('horaire_connexions', 'type')) {
            DB::statement("ALTER TABLE `horaire_connexions` ADD `type` ENUM('normale','majoree') NOT NULL DEFAULT 'normale' AFTER `heure_fin`");
        }
    }
};
