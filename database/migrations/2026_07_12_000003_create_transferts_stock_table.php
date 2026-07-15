<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transferts_stock')) {
            Schema::create('transferts_stock', function (Blueprint $table) {
                $table->id();

                // Points de vente concernés
                $table->foreignId('source_boutique_id')->constrained('boutiques')->cascadeOnDelete();
                $table->foreignId('destination_boutique_id')->constrained('boutiques')->cascadeOnDelete();
                $table->foreignId('produit_id')->constrained('produits')->cascadeOnDelete();

                // Acteurs
                $table->foreignId('initiator_id')->nullable()->constrained('users')->nullOnDelete();      // magasinier
                $table->foreignId('source_user_id')->nullable()->constrained('users')->nullOnDelete();    // vendeur source
                $table->foreignId('destination_user_id')->nullable()->constrained('users')->nullOnDelete(); // vendeur destination

                // Quantités aux 3 étapes
                $table->unsignedInteger('quantite_demandee');
                $table->unsignedInteger('quantite_autorisee')->nullable();
                $table->unsignedInteger('quantite_recue')->nullable();

                // Prix figés au moment de la sortie du stock source (conserve le coût réel)
                $table->decimal('prix_achat_unitaire', 12, 2)->nullable();
                $table->decimal('prix_vente_unitaire', 12, 2)->nullable();
                $table->decimal('prix_vente_grossiste_unitaire', 12, 2)->nullable();

                // en_attente_source | autorise | recu | refuse | probleme
                $table->string('statut')->default('en_attente_source');
                $table->string('note')->nullable();

                $table->timestamp('authorized_at')->nullable();
                $table->timestamp('received_at')->nullable();
                $table->timestamps();

                $table->index(['source_boutique_id', 'statut']);
                $table->index(['destination_boutique_id', 'statut']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transferts_stock');
    }
};
