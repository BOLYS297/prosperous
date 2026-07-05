<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE `recharges` MODIFY `statut` ENUM('en_attente','confirmee','confirmee_par_magasinier','anomalie','approuvee','rejetee') NOT NULL DEFAULT 'en_attente';");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE `recharges` MODIFY `statut` ENUM('en_attente','confirmee','anomalie') NOT NULL DEFAULT 'en_attente';");
        }
    }
};
