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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('monthly_salary')->default(0)->after('device_token')->comment('Salaire mensuel de l\'employé en FCFA');
        });

        Schema::create('salary_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('period', 7)->comment('Période de paie au format YYYY-MM');
            $table->unsignedInteger('gross_salary')->default(0)->comment('Salaire brut mensuel');
            $table->unsignedInteger('carryover_previous')->default(0)->comment('Report de déduction du mois précédent');
            $table->unsignedInteger('deductions')->default(0)->comment('Total déductions approuvées pour le mois');
            $table->unsignedInteger('net_salary')->default(0)->comment('Salaire net à payer après déductions');
            $table->unsignedInteger('carryover_next')->default(0)->comment('Report de déduction vers le mois suivant');
            $table->timestamps();

            $table->unique(['user_id', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_periods');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('monthly_salary');
        });
    }
};
