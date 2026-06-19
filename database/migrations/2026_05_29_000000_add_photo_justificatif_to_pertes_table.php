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
        Schema::table('pertes', function (Blueprint $table) {
            if (! Schema::hasColumn('pertes', 'photo_justificatif')) {
                $table->string('photo_justificatif')->nullable()->after('raison');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pertes', function (Blueprint $table) {
            if (Schema::hasColumn('pertes', 'photo_justificatif')) {
                $table->dropColumn('photo_justificatif');
            }
        });
    }
};
