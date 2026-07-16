<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Paiement des salaires : jusqu'ici la paie n'était qu'un rapport recalculé
     * à chaque affichage. On enregistre désormais le règlement, et le montant
     * payé est FIGÉ (paid_amount) : une déduction validée ou une vente
     * enregistrée après coup ne doit pas réécrire un mois déjà réglé.
     */
    public function up(): void
    {
        Schema::table('salary_periods', function (Blueprint $table) {
            if (! Schema::hasColumn('salary_periods', 'status')) {
                $table->enum('status', ['en_attente', 'paye'])->default('en_attente')->after('carryover_next');
            }
            if (! Schema::hasColumn('salary_periods', 'paid_amount')) {
                $table->decimal('paid_amount', 12, 2)->nullable()->after('status');
            }
            if (! Schema::hasColumn('salary_periods', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('paid_amount');
            }
            if (! Schema::hasColumn('salary_periods', 'paid_by')) {
                $table->foreignId('paid_by')->nullable()->after('paid_at')->constrained('users')->nullOnDelete();
            }
            // Source du règlement : 'admin' (solde personnel) ou 'boutique'.
            if (! Schema::hasColumn('salary_periods', 'payment_source_type')) {
                $table->string('payment_source_type')->nullable()->after('paid_by');
            }
            if (! Schema::hasColumn('salary_periods', 'payment_source_id')) {
                $table->unsignedBigInteger('payment_source_id')->nullable()->after('payment_source_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('salary_periods', function (Blueprint $table) {
            if (Schema::hasColumn('salary_periods', 'paid_by')) {
                $table->dropConstrainedForeignId('paid_by');
            }
            foreach (['status', 'paid_amount', 'paid_at', 'payment_source_type', 'payment_source_id'] as $col) {
                if (Schema::hasColumn('salary_periods', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
