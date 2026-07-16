<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Une avance de caisse peut désormais être portée par l'ADMIN lui-même :
     * boutique_id = NULL signifie « dette personnelle de l'admin », remboursée
     * progressivement depuis son solde personnel (admin_id = le débiteur).
     * Pour une avance de boutique, boutique_id reste renseigné et rien ne change.
     */
    public function up(): void
    {
        if (! Schema::hasTable('avances_caisse')) {
            return;
        }

        // La clé étrangère bloque la modification de la colonne : on la retire,
        // on rend la colonne nullable, puis on la remet.
        $fk = collect(DB::select("
            SELECT CONSTRAINT_NAME AS name
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'avances_caisse'
              AND COLUMN_NAME = 'boutique_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        "))->first();

        if ($fk) {
            DB::statement("ALTER TABLE `avances_caisse` DROP FOREIGN KEY `{$fk->name}`");
        }

        DB::statement('ALTER TABLE `avances_caisse` MODIFY `boutique_id` BIGINT UNSIGNED NULL');

        DB::statement('ALTER TABLE `avances_caisse` ADD CONSTRAINT `avances_caisse_boutique_id_foreign`
            FOREIGN KEY (`boutique_id`) REFERENCES `boutiques` (`id`) ON DELETE CASCADE');
    }

    public function down(): void
    {
        if (! Schema::hasTable('avances_caisse')) {
            return;
        }

        // Les dettes personnelles de l'admin n'ont pas d'équivalent boutique :
        // on les supprime avant de rétablir la contrainte NOT NULL.
        DB::table('avances_caisse')->whereNull('boutique_id')->delete();

        DB::statement('ALTER TABLE `avances_caisse` DROP FOREIGN KEY `avances_caisse_boutique_id_foreign`');
        DB::statement('ALTER TABLE `avances_caisse` MODIFY `boutique_id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `avances_caisse` ADD CONSTRAINT `avances_caisse_boutique_id_foreign`
            FOREIGN KEY (`boutique_id`) REFERENCES `boutiques` (`id`) ON DELETE CASCADE');
    }
};
