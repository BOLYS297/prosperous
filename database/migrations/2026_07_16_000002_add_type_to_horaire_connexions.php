<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chaque tranche horaire porte désormais son type de tarification :
        //  - normale : prix habituel ;
        //  - majoree : prix hors heures, la majoration revient à l'employé.
        // La connexion reste autorisée sur les deux types : c'est uniquement le
        // prix appliqué qui change.
        if (! Schema::hasColumn('horaire_connexions', 'type')) {
            DB::statement("ALTER TABLE `horaire_connexions` ADD `type` ENUM('normale','majoree') NOT NULL DEFAULT 'normale' AFTER `heure_fin`");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('horaire_connexions', 'type')) {
            DB::statement("ALTER TABLE `horaire_connexions` DROP COLUMN `type`");
        }
    }
};
