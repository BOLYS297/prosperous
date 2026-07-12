<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('avances_caisse')) {
            Schema::create('avances_caisse', function (Blueprint $table) {
                $table->id();
                $table->foreignId('boutique_id')->constrained('boutiques')->cascadeOnDelete();
                $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
                $table->decimal('montant', 12, 2);
                $table->decimal('montant_rembourse', 12, 2)->default(0);
                $table->string('motif')->nullable();
                $table->string('statut')->default('en_cours'); // en_cours | remboursee
                $table->timestamps();

                $table->index(['boutique_id', 'statut']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('avances_caisse');
    }
};
