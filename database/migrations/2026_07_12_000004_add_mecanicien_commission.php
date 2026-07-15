<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La colonne role est un ENUM : il faut y ajouter 'mecanicien'.
        // (doctrine/dbal n'étant pas requis, on passe par du SQL brut MySQL)
        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('super_admin','admin','magasinier','boutiquier','mecanicien') NOT NULL");

        // Pourcentage de commission sur le bénéfice, propre à chaque mécanicien.
        if (! Schema::hasColumn('users', 'commission_percent')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('commission_percent', 5, 2)->nullable()->after('monthly_salary');
            });
        }

        // Vente attribuée à un mécanicien (ventes client uniquement).
        if (! Schema::hasColumn('ventes', 'mecanicien_id')) {
            Schema::table('ventes', function (Blueprint $table) {
                $table->foreignId('mecanicien_id')->nullable()->after('user_id')
                    ->constrained('users')->nullOnDelete();
            });
        }

        Schema::table('vente_lignes', function (Blueprint $table) {
            // Coût FIGÉ au moment de la vente : indispensable pour calculer le
            // bénéfice a posteriori (le coût FIFO change dans le temps).
            if (! Schema::hasColumn('vente_lignes', 'prix_achat_unitaire')) {
                $table->decimal('prix_achat_unitaire', 12, 2)->nullable()->after('prix_unitaire');
            }
            // Commission FIGÉE du mécanicien pour cette ligne.
            if (! Schema::hasColumn('vente_lignes', 'commission_mecanicien')) {
                $table->decimal('commission_mecanicien', 12, 2)->nullable()->after('prix_achat_unitaire');
            }
        });
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('super_admin','magasinier','boutiquier') NOT NULL");

        if (Schema::hasColumn('vente_lignes', 'commission_mecanicien')) {
            Schema::table('vente_lignes', fn (Blueprint $t) => $t->dropColumn('commission_mecanicien'));
        }
        if (Schema::hasColumn('vente_lignes', 'prix_achat_unitaire')) {
            Schema::table('vente_lignes', fn (Blueprint $t) => $t->dropColumn('prix_achat_unitaire'));
        }
        if (Schema::hasColumn('ventes', 'mecanicien_id')) {
            Schema::table('ventes', function (Blueprint $table) {
                $table->dropForeign(['mecanicien_id']);
                $table->dropColumn('mecanicien_id');
            });
        }
        if (Schema::hasColumn('users', 'commission_percent')) {
            Schema::table('users', fn (Blueprint $t) => $t->dropColumn('commission_percent'));
        }
    }
};
