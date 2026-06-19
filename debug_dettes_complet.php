<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Achat;
use App\Models\Boutique;
use App\Models\User;

echo "=== DIAGNOSTIC COMPLET DETTES ===\n\n";

// 1. Boutiques
echo "1. TOUTES LES BOUTIQUES:\n";
$boutiques = Boutique::all();
foreach ($boutiques as $b) {
    echo "  Boutique #{$b->id}: {$b->nom} (type: {$b->type}, solde: {$b->solde})\n";
}
echo "\n";

// 2. Utilisateurs par boutique
echo "2. UTILISATEURS PAR BOUTIQUE:\n";
$users = User::whereNotNull('boutique_id')->get();
foreach ($users as $u) {
    $boutique = $u->boutique;
    $boutiqueName = $boutique ? $boutique->nom : "N/A";
    echo "  {$u->nom_utilisateur} (role: {$u->role}) -> Boutique {$u->boutique_id} ($boutiqueName)\n";
}
echo "\n";

// 3. Dettes totales en base
echo "3. RÉSUMÉ DES DETTES:\n";
$toutesLesDettesBrutes = Achat::where('statut', 'dette')->with('paiements', 'recharge')->get();
echo "  Nombre total de dettes: {$toutesLesDettesBrutes->count()}\n";
$totalDette = $toutesLesDettesBrutes->sum(fn($a) => $a->reste_a_payer);
echo "  Montant total restant: {$totalDette}\n";
echo "\n";

// 4. Dettes par boutique (destination)
echo "4. DETTES PAR BOUTIQUE (destination_id / boutique_id):\n";
foreach ($boutiques as $b) {
    $dettes = Achat::where('statut', 'dette')
        ->where(function ($query) use ($b) {
            $query->where('boutique_id', $b->id)
                ->orWhereHas('recharge', function ($query) use ($b) {
                    $query->where('destination_id', $b->id);
                });
        })
        ->get();

    $total = $dettes->sum(fn($a) => $a->reste_a_payer);
    echo "  Boutique #{$b->id} ({$b->nom}): {$dettes->count()} dettes, montant: {$total}\n";
    foreach ($dettes as $achat) {
        echo "    - Achat #{$achat->id}: boutique_id={$achat->boutique_id}, recharge.destination_id=" . ($achat->recharge ? $achat->recharge->destination_id : "N/A") . ", reste={$achat->reste_a_payer}\n";
    }
}
echo "\n";

// 5. Vue Admin
echo "5. VUE ADMIN (toutes les dettes sans filtre):\n";
$adminDettesFournisseurs = Achat::where('statut', 'dette')
    ->with('paiements')
    ->get()
    ->sum(fn($achat) => $achat->reste_a_payer);
echo "  Total dettes visibles en admin: {$adminDettesFournisseurs}\n";
echo "\n";

// 6. Détails des dettes
echo "6. DÉTAILS COMPLETS DES DETTES:\n";
foreach ($toutesLesDettesBrutes as $achat) {
    echo "  Achat #{$achat->id}:\n";
    echo "    - Montant total: {$achat->montant_total}\n";
    echo "    - Montant payé: {$achat->montant_paye}\n";
    echo "    - Reste à payer: {$achat->reste_a_payer}\n";
    echo "    - Boutique ID: {$achat->boutique_id}\n";
    echo "    - Fournisseur ID: {$achat->fournisseur_id}\n";
    if ($achat->recharge) {
        echo "    - Recharge destination: {$achat->recharge->destination_id}\n";
    }
    echo "    - Paiements: " . $achat->paiements->count() . "\n";
}

echo "\n=== FIN DIAGNOSTIC ===\n";
