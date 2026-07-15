<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Solde personnel (recettes) de chaque administrateur.
        if (! Schema::hasColumn('users', 'solde_personnel')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('solde_personnel', 14, 2)->default(0)->after('monthly_salary');
            });
        }

        // Grand livre du solde personnel : montant SIGNÉ (+ = entrée, - = sortie).
        if (! Schema::hasTable('admin_solde_mouvements')) {
            Schema::create('admin_solde_mouvements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
                // recette | retrait | achat | depense | remboursement
                $table->string('type');
                $table->decimal('montant', 14, 2); // signé
                $table->string('motif')->nullable();
                $table->foreignId('boutique_id')->nullable()->constrained('boutiques')->nullOnDelete();
                $table->string('source_type')->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->timestamps();

                $table->index(['admin_id', 'created_at']);
            });
        }

        // Un paiement d'achat peut désormais venir du solde personnel de l'admin
        // (aucune boutique concernée).
        DB::statement('ALTER TABLE `achat_paiements` MODIFY `boutique_id` BIGINT UNSIGNED NULL');

        // Achat à crédit imputé au solde personnel d'un admin.
        if (! Schema::hasColumn('achats', 'debit_admin_id')) {
            Schema::table('achats', function (Blueprint $table) {
                $table->foreignId('debit_admin_id')->nullable()->after('debit_boutique_id')
                    ->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('achats', 'debit_admin_id')) {
            Schema::table('achats', function (Blueprint $table) {
                $table->dropForeign(['debit_admin_id']);
                $table->dropColumn('debit_admin_id');
            });
        }

        Schema::dropIfExists('admin_solde_mouvements');

        if (Schema::hasColumn('users', 'solde_personnel')) {
            Schema::table('users', fn (Blueprint $t) => $t->dropColumn('solde_personnel'));
        }
    }
};
