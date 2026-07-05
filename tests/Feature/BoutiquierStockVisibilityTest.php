<?php

namespace Tests\Feature;

use App\Models\Boutique;
use App\Models\Produit;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoutiquierStockVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_boutiquier_can_view_stock_from_other_boutiques_on_dashboard(): void
    {
        $currentBoutique = Boutique::create(['nom' => 'Boutique A', 'type' => 'boutique']);
        $otherBoutique = Boutique::create(['nom' => 'Boutique B', 'type' => 'boutique']);
        $produit = Produit::create([
            'nom' => 'Produit test',
            'reference' => 'REF-001',
            'prix_achat' => 1000,
            'prix_vente' => 1500,
        ]);

        Stock::create(['boutique_id' => $currentBoutique->id, 'produit_id' => $produit->id, 'quantite' => 5]);
        Stock::create(['boutique_id' => $otherBoutique->id, 'produit_id' => $produit->id, 'quantite' => 8]);

        $boutiquier = User::create([
            'nom_utilisateur' => 'boutiquier1',
            'email' => 'boutiquier1@example.com',
            'password' => bcrypt('password'),
            'role' => 'boutiquier',
            'boutique_id' => $currentBoutique->id,
        ]);

        $response = $this->actingAs($boutiquier, 'web')->get(route('boutiquier.dashboard'));

        $response->assertOk();
        $response->assertSee($otherBoutique->nom);
        $response->assertSee('8');
    }
}
