<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demande_transferts', function (Blueprint $table) {
            $table->integer('quantite_recue')->nullable()->after('quantite_expediee');
        });
    }

    public function down(): void
    {
        Schema::table('demande_transferts', function (Blueprint $table) {
            $table->dropColumn('quantite_recue');
        });
    }
};
