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
        if (Schema::hasTable('deductions')) {
            // Check if columns exist, add if missing
            if (!Schema::hasColumn('deductions', 'actual_login_at')) {
                Schema::table('deductions', function (Blueprint $table) {
                    $table->dateTime('actual_login_at')->nullable()->after('scheduled_start');
                });
            }

            if (!Schema::hasColumn('deductions', 'scheduled_start')) {
                Schema::table('deductions', function (Blueprint $table) {
                    $table->time('scheduled_start')->nullable();
                });
            }

            if (!Schema::hasColumn('deductions', 'minutes_late')) {
                Schema::table('deductions', function (Blueprint $table) {
                    $table->unsignedInteger('minutes_late')->default(0);
                });
            }

            if (!Schema::hasColumn('deductions', 'description')) {
                Schema::table('deductions', function (Blueprint $table) {
                    $table->text('description')->nullable();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback
    }
};
