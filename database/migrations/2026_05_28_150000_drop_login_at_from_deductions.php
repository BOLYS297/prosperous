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
        if (!Schema::hasTable('deductions')) {
            return;
        }

        Schema::table('deductions', function (Blueprint $table) {
            if (Schema::hasColumn('deductions', 'login_at') && Schema::hasColumn('deductions', 'actual_login_at')) {
                $table->dropColumn('login_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('deductions')) {
            return;
        }

        Schema::table('deductions', function (Blueprint $table) {
            if (!Schema::hasColumn('deductions', 'login_at')) {
                $table->dateTime('login_at')->nullable()->after('user_id');
            }
        });
    }
};
