<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recharges', function (Blueprint $table) {
            if (!Schema::hasColumn('recharges', 'raison_rejet')) {
                $table->text('raison_rejet')->nullable()->after('statut');
            }
        });
    }

    public function down(): void
    {
        Schema::table('recharges', function (Blueprint $table) {
            if (Schema::hasColumn('recharges', 'raison_rejet')) {
                $table->dropColumn('raison_rejet');
            }
        });
    }
};
