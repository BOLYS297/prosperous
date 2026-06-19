<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prix_grossistes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grossiste_id')->constrained('grossistes')->onDelete('cascade');
            $table->foreignId('produit_id')->constrained('produits')->onDelete('cascade');
            $table->decimal('prix_achat', 12, 2);
            $table->decimal('prix_vente', 12, 2);
            $table->unique(['grossiste_id', 'produit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prix_grossistes');
    }
};
