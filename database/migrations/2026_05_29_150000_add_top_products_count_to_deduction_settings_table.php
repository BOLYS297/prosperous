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
        Schema::table('deduction_settings', function (Blueprint $table) {
            $table->unsignedInteger('top_products_count')->default(5)->after('hourly_retard_amount')->comment('Nombre de produits à afficher dans le top produits vendus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deduction_settings', function (Blueprint $table) {
            $table->dropColumn('top_products_count');
        });
    }
};
