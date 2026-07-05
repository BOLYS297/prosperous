<?php

namespace Tests\Feature;

use App\Http\Controllers\Boutiquier\VenteController;
use App\Models\Boutique;
use App\Models\Produit;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class StockBatchPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_uses_old_stock_batch_then_new_batch_prices(): void
    {
        $boutique = Boutique::create(['nom' => 'Boutique test', 'type' => 'boutique']);
        $produit = Produit::create([
            'nom' => 'Produit lot',
            'reference' => 'LOT-01',
            'prix_achat' => 1000,
            'prix_vente' => 1500,
        ]);

        $oldBatch = Stock::create([
            'boutique_id' => $boutique->id,
            'produit_id' => $produit->id,
            'quantite' => 5,
            'prix_achat_unitaire' => 1000,
            'prix_vente_unitaire' => 1500,
        ]);

        $newBatch = Stock::create([
            'boutique_id' => $boutique->id,
            'produit_id' => $produit->id,
            'quantite' => 3,
            'prix_achat_unitaire' => 1200,
            'prix_vente_unitaire' => 1800,
        ]);

        $boutiquier = User::create([
            'nom_utilisateur' => 'boutiquierbatch',
            'email' => 'boutiquierbatch@example.com',
            'password' => bcrypt('password'),
            'role' => 'boutiquier',
            'boutique_id' => $boutique->id,
        ]);

        Auth::login($boutiquier);

        $request = Request::create('/boutiquier/ventes', 'POST', [
            'produit_id' => $produit->id,
            'quantite' => 6,
        ]);

        $controller = app(VenteController::class);
        $response = $controller->store($request);

        $this->assertTrue($response->isRedirect());
        $oldBatch->refresh();
        $newBatch->refresh();
        $this->assertEquals(0, $oldBatch->quantite);
        $this->assertEquals(2, $newBatch->quantite);

        $vente = \App\Models\Vente::latest()->first();
        $this->assertNotNull($vente);
        $this->assertEquals(9300, $vente->montant_total);
        $this->assertEquals(1550, $vente->lignes()->first()->prix_unitaire);
    }
}
