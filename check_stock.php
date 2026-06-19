<?php
$user = \App\Models\User::where('role', 'boutiquier')->first();
if ($user) {
    echo "Boutiquier: {$user->nom_utilisateur} boutique_id={$user->boutique_id}\n";
    $stocks = \App\Models\Stock::where('boutique_id', $user->boutique_id)->get();
    echo "Stocks: {$stocks->count()}\n";
    foreach ($stocks as $s) {
        $p = \App\Models\Produit::find($s->produit_id);
        echo "  Produit#{$s->produit_id} ({$p->nom}) qty={$s->quantite}\n";
    }
} else {
    echo "Aucun utilisateur boutiquier trouvé!\n";
}
