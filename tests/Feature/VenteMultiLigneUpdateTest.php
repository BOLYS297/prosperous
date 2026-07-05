<?php

namespace Tests\Feature;

use App\Http\Controllers\Boutiquier\VenteController;
use App\Models\Boutique;
use App\Models\Produit;
use App\Models\Stock;
use App\Models\User;
use App\Models\Vente;
use App\Models\VenteLigne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class VenteMultiLigneUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_boutiquier_can_update_a_sale_with_multiple_lines(): void
    {
        $boutique = Boutique::create(['nom' => 'Boutique A', 'type' => 'boutique']);
        $produitA = Produit::create([
            'nom' => 'Produit A',
            'reference' => 'REF-A',
            'prix_achat' => 1000,
            'prix_vente' => 1500,
        ]);
        $produitB = Produit::create([
            'nom' => 'Produit B',
            'reference' => 'REF-B',
            'prix_achat' => 800,
            'prix_vente' => 1200,
        ]);

        Stock::create(['boutique_id' => $boutique->id, 'produit_id' => $produitA->id, 'quantite' => 10]);
        Stock::create(['boutique_id' => $boutique->id, 'produit_id' => $produitB->id, 'quantite' => 10]);

        $boutiquier = User::create([
            'nom_utilisateur' => 'boutiquier2',
            'email' => 'boutiquier2@example.com',
            'password' => bcrypt('password'),
            'role' => 'boutiquier',
            'boutique_id' => $boutique->id,
        ]);

        $vente = Vente::create([
            'boutique_id' => $boutique->id,
            'user_id' => $boutiquier->id,
            'montant_total' => 3000,
            'grossiste_id' => null,
        ]);

        VenteLigne::create([
            'vente_id' => $vente->id,
            'produit_id' => $produitA->id,
            'quantite' => 2,
            'prix_unitaire' => 1500,
            'est_grossiste' => false,
        ]);
        VenteLigne::create([
            'vente_id' => $vente->id,
            'produit_id' => $produitB->id,
            'quantite' => 1,
            'prix_unitaire' => 1200,
            'est_grossiste' => false,
        ]);

        Auth::login($boutiquier);

        $request = Request::create(route('boutiquier.ventes.update', $vente), 'PUT', [
            'lignes' => [
                ['produit_id' => $produitA->id, 'quantite' => 3],
                ['produit_id' => $produitB->id, 'quantite' => 2],
            ],
        ]);

        $controller = app(VenteController::class);
        $response = $controller->update($request, $vente);

        $this->assertTrue($response->isRedirect());
        $vente->refresh();
        $this->assertEquals(6900, $vente->montant_total);
        $this->assertCount(2, $vente->lignes);
        $this->assertEquals(3, $vente->lignes()->where('produit_id', $produitA->id)->first()->quantite);
        $this->assertEquals(2, $vente->lignes()->where('produit_id', $produitB->id)->first()->quantite);
    }
}
