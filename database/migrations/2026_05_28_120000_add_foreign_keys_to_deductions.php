<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ajoute les clés étrangères de "deductions" UNIQUEMENT si elles
     * n'existent pas déjà. Sur une base neuve, create_deductions_table les
     * crée déjà (via ->constrained()), donc cette migration ne fait rien.
     * Évite l'erreur MySQL 1826 "Duplicate foreign key constraint name".
     */
    public function up(): void
    {
        if (! Schema::hasTable('deductions') || Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $existing = collect(DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'deductions'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
        ))->pluck('CONSTRAINT_NAME')->all();

        Schema::table('deductions', function (Blueprint $table) use ($existing) {
            if (
                Schema::hasColumn('deductions', 'user_id')
                && ! in_array('deductions_user_id_foreign', $existing, true)
            ) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }

            if (
                Schema::hasColumn('deductions', 'approved_by')
                && ! in_array('deductions_approved_by_foreign', $existing, true)
            ) {
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pas de rollback : les clés étrangères appartiennent à create_deductions_table.
    }
};
