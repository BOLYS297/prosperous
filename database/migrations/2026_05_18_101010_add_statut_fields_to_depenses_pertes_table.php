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
        Schema::table('depenses', function (Blueprint $table) {
            if (!Schema::hasColumn('depenses', 'statut')) {
                $table->string('statut')->default('approved')->after('photo_justificatif');
            }
            if (!Schema::hasColumn('depenses', 'admin_id')) {
                $table->foreignId('admin_id')->nullable()->after('statut')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('depenses', 'rejet_reason')) {
                $table->text('rejet_reason')->nullable()->after('admin_id');
            }
            if (!Schema::hasColumn('depenses', 'validated_at')) {
                $table->timestamp('validated_at')->nullable()->after('rejet_reason');
            }
        });

        Schema::table('pertes', function (Blueprint $table) {
            if (!Schema::hasColumn('pertes', 'statut')) {
                $table->string('statut')->default('approved')->after('raison');
            }
            if (!Schema::hasColumn('pertes', 'admin_id')) {
                $table->foreignId('admin_id')->nullable()->after('statut')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('pertes', 'rejet_reason')) {
                $table->text('rejet_reason')->nullable()->after('admin_id');
            }
            if (!Schema::hasColumn('pertes', 'validated_at')) {
                $table->timestamp('validated_at')->nullable()->after('rejet_reason');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('depenses', function (Blueprint $table) {
            if (Schema::hasColumn('depenses', 'validated_at')) {
                $table->dropColumn('validated_at');
            }
            if (Schema::hasColumn('depenses', 'rejet_reason')) {
                $table->dropColumn('rejet_reason');
            }
            if (Schema::hasColumn('depenses', 'admin_id')) {
                $table->dropForeign(['admin_id']);
                $table->dropColumn('admin_id');
            }
            if (Schema::hasColumn('depenses', 'statut')) {
                $table->dropColumn('statut');
            }
        });

        Schema::table('pertes', function (Blueprint $table) {
            if (Schema::hasColumn('pertes', 'validated_at')) {
                $table->dropColumn('validated_at');
            }
            if (Schema::hasColumn('pertes', 'rejet_reason')) {
                $table->dropColumn('rejet_reason');
            }
            if (Schema::hasColumn('pertes', 'admin_id')) {
                $table->dropForeign(['admin_id']);
                $table->dropColumn('admin_id');
            }
            if (Schema::hasColumn('pertes', 'statut')) {
                $table->dropColumn('statut');
            }
        });
    }
};
