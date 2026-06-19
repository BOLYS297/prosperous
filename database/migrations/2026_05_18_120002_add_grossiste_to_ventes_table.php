<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            $table->foreignId('grossiste_id')->nullable()->constrained('grossistes')->onDelete('set null');
        });

        Schema::table('vente_lignes', function (Blueprint $table) {
            $table->boolean('est_grossiste')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            $table->dropForeign(['grossiste_id']);
            $table->dropColumn('grossiste_id');
        });

        Schema::table('vente_lignes', function (Blueprint $table) {
            $table->dropColumn('est_grossiste');
        });
    }
};
