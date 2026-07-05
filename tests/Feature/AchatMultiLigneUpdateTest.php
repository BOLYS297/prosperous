<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AchatController;
use App\Models\Achat;
use App\Models\AchatLigne;
use App\Models\Boutique;
use App\Models\Fournisseur;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AchatMultiLigneUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_an_achat_with_multiple_lines(): void
    {
        $magasin = Boutique::create(['nom' => 'Magasin central', 'type' => 'magasin']);
        $fournisseur = Fournisseur::create(['nom' => 'Fournisseur test']);
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

        $admin = User::create([
            'nom_utilisateur' => 'admin1',
            'email' => 'admin1@example.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'boutique_id' => $magasin->id,
        ]);

        $achat = Achat::create([
            'fournisseur_id' => $fournisseur->id,
            'boutique_id' => $magasin->id,
            'debit_boutique_id' => null,
            'statut' => 'dette',
            'montant_total' => 5000,
        ]);

        AchatLigne::create([
            'achat_id' => $achat->id,
            'produit_id' => $produitA->id,
            'quantite' => 1,
            'prix_unitaire' => 2500,
        ]);
        AchatLigne::create([
            'achat_id' => $achat->id,
            'produit_id' => $produitB->id,
            'quantite' => 1,
            'prix_unitaire' => 2500,
        ]);

        Auth::login($admin);

        $request = Request::create(route('admin.achats.update', $achat), 'PUT', [
            'fournisseur_id' => $fournisseur->id,
            'boutique_id' => $magasin->id,
            'statut' => 'dette',
            'lignes' => [
                ['produit_id' => $produitA->id, 'quantite' => 2, 'prix_unitaire' => 3000],
                ['produit_id' => $produitB->id, 'quantite' => 3, 'prix_unitaire' => 1800],
            ],
        ]);

        $controller = app(AchatController::class);
        $response = $controller->update($request, $achat);

        $this->assertTrue($response->isRedirect());
        $achat->refresh();
        $this->assertEquals(11400, $achat->montant_total);
        $this->assertCount(2, $achat->lignes);
        $this->assertEquals(2, $achat->lignes()->where('produit_id', $produitA->id)->first()->quantite);
        $this->assertEquals(3, $achat->lignes()->where('produit_id', $produitB->id)->first()->quantite);
    }
}
