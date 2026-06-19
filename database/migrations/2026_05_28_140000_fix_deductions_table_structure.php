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
            if (Schema::hasColumn('deductions', 'login_at') && !Schema::hasColumn('deductions', 'actual_login_at')) {
                $table->renameColumn('login_at', 'actual_login_at');
            }
            if (Schema::hasColumn('deductions', 'admin_id') && !Schema::hasColumn('deductions', 'approved_by')) {
                $table->renameColumn('admin_id', 'approved_by');
            }
            if (Schema::hasColumn('deductions', 'applied_at') && !Schema::hasColumn('deductions', 'approved_at')) {
                $table->renameColumn('applied_at', 'approved_at');
            }
        });

        // Change scheduled_start to TIME and ensure approved_by is unsigned big integer nullable.
        Schema::table('deductions', function (Blueprint $table) {
            if (Schema::hasColumn('deductions', 'scheduled_start')) {
                $table->time('scheduled_start')->nullable()->change();
            }
            if (Schema::hasColumn('deductions', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->change();
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
            if (Schema::hasColumn('deductions', 'actual_login_at') && !Schema::hasColumn('deductions', 'login_at')) {
                $table->renameColumn('actual_login_at', 'login_at');
            }
            if (Schema::hasColumn('deductions', 'approved_by') && !Schema::hasColumn('deductions', 'admin_id')) {
                $table->renameColumn('approved_by', 'admin_id');
            }
            if (Schema::hasColumn('deductions', 'approved_at') && !Schema::hasColumn('deductions', 'applied_at')) {
                $table->renameColumn('approved_at', 'applied_at');
            }
        });

        Schema::table('deductions', function (Blueprint $table) {
            if (Schema::hasColumn('deductions', 'scheduled_start')) {
                $table->dateTime('scheduled_start')->nullable()->change();
            }
        });
    }
};
