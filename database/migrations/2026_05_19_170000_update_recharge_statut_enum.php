<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `recharges` MODIFY `statut` ENUM('en_attente','confirmee','confirmee_par_magasinier','anomalie','approuvee','rejetee') NOT NULL DEFAULT 'en_attente';");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `recharges` MODIFY `statut` ENUM('en_attente','confirmee','anomalie') NOT NULL DEFAULT 'en_attente';");
    }
};
