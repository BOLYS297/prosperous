<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Le magasinier peut désormais REFUSER une demande de stock.
        DB::statement("ALTER TABLE `demande_transferts` MODIFY `statut` ENUM('en_attente','expediee','livree','probleme','refusee') NOT NULL DEFAULT 'en_attente'");
    }

    public function down(): void
    {
        DB::statement("UPDATE `demande_transferts` SET `statut` = 'en_attente' WHERE `statut` = 'refusee'");
        DB::statement("ALTER TABLE `demande_transferts` MODIFY `statut` ENUM('en_attente','expediee','livree','probleme') NOT NULL DEFAULT 'en_attente'");
    }
};
