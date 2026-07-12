<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use App\Models\LogActivite;
use App\Models\Produit;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function edit(Boutique $boutique)
    {
        $produits = Produit::orderBy('nom')->get();

        // Stock actuel (somme des lots) par produit pour cette boutique.
        $stockActuel = Stock::where('boutique_id', $boutique->id)
            ->selectRaw('produit_id, SUM(quantite) as total')
            ->groupBy('produit_id')
            ->pluck('total', 'produit_id');

        return view('admin.stocks.edit', compact('boutique', 'produits', 'stockActuel'));
    }

    public function update(Request $request, Boutique $boutique)
    {
        $request->validate([
            'stocks' => 'required|array',
            'stocks.*.produit_id' => 'required|exists:produits,id',
            'stocks.*.quantite' => 'required|integer|min:0',
        ]);

        // Stock actuel par produit (référence avant ajustement).
        $current = Stock::where('boutique_id', $boutique->id)
            ->selectRaw('produit_id, SUM(quantite) as total')
            ->groupBy('produit_id')
            ->pluck('total', 'produit_id');

        $changes = 0;

        DB::transaction(function () use ($request, $boutique, $current, &$changes) {
            foreach ($request->input('stocks', []) as $row) {
                $produitId = (int) $row['produit_id'];
                $target = max(0, (int) $row['quantite']);
                $now = (int) ($current[$produitId] ?? 0);

                if ($target === $now) {
                    continue;
                }

                if ($target > $now) {
                    // Augmentation : nouveau lot avec la différence (prix du produit).
                    $produit = Produit::find($produitId);
                    Stock::addBatch(
                        $boutique->id,
                        $produitId,
                        $target - $now,
                        $produit ? (float) $produit->getRawOriginal('prix_achat') : 0,
                        $produit ? (float) $produit->getRawOriginal('prix_vente') : 0,
                        null,
                        'ajustement_admin',
                        Auth::id()
                    );
                } else {
                    // Diminution : retrait FIFO de la différence.
                    Stock::reduceQuantity($boutique->id, $produitId, $now - $target);
                }

                $changes++;
            }
        });

        LogActivite::create([
            'user_id' => Auth::id(),
            'action' => 'admin.stocks.update',
            'description' => "Ajustement manuel du stock de « {$boutique->nom} » : {$changes} produit(s) modifié(s).",
        ]);

        return redirect()->route('admin.stocks.edit', $boutique)
            ->with('success', "Stock mis à jour : {$changes} produit(s) modifié(s).");
    }
}
