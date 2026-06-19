<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('recharges', function (Blueprint $table) {
            if (!Schema::hasColumn('recharges', 'fournisseur_id')) {
                $table->foreignId('fournisseur_id')->nullable()->after('source_id')->constrained('fournisseurs')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recharges', function (Blueprint $table) {
            if (Schema::hasColumn('recharges', 'fournisseur_id')) {
                $table->dropForeign(['fournisseur_id']);
                $table->dropColumn('fournisseur_id');
            }
        });
    }
};
