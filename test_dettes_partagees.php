<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Achat;
use App\Models\User;
use App\Models\Boutique;

echo "=== TEST DETTES PARTAGÉES ===\n\n";

// 1. Afficher toutes les boutiques
echo "1. BOUTIQUES:\n";
$boutiques = Boutique::all();
foreach ($boutiques as $b) {
    echo "  Boutique #{$b->id}: {$b->nom} (type: {$b->type})\n";
}
echo "\n";

// 2. Vérifier les dettes
echo "2. DETTES EN BASE (statut='dette'):\n";
$dettes = Achat::where('statut', 'dette')->get();
echo "  Nombre total: {$dettes->count()}\n";
foreach ($dettes as $d) {
    echo "  - Achat #{$d->id}: montant={$d->montant_total}, reste={$d->reste_a_payer}, debit_boutique_id=" . ($d->debit_boutique_id ?? 'NULL') . "\n";
}
echo "\n";

// 3. Test: Chaque boutique voir TOUTES les dettes
echo "3. VISIBILITÉ DES DETTES PAR BOUTIQUE:\n";
foreach ($boutiques as $boutique) {
    $dettesVisibles = Achat::where('statut', 'dette')->get()->filter(fn($a) => $a->reste_a_payer > 0);
    echo "  Boutique #{$boutique->id} ({$boutique->nom}): {$dettesVisibles->count()} dettes visibles\n";
    foreach ($dettesVisibles as $d) {
        echo "    - Achat #{$d->id}: {$d->reste_a_payer} FCFA\n";
    }
}
echo "\n";

// 4. Test: Vérifier qu'un boutiquier voit toutes les dettes
echo "4. TEST BOUTIQUIER 'mauricelle':\n";
$user = User::where('nom_utilisateur', 'mauricelle')->first();
if ($user) {
    echo "  Utilisateur: {$user->nom_utilisateur} (Boutique: {$user->boutique->nom})\n";
    $dettesVuesDashboard = Achat::with('paiements')
        ->where('statut', 'dette')
        ->get()
        ->filter(fn($achat) => $achat->reste_a_payer > 0);
    echo "  Dettes visibles au dashboard: {$dettesVuesDashboard->count()}\n";
    foreach ($dettesVuesDashboard as $d) {
        echo "    - Achat #{$d->id}: {$d->reste_a_payer} FCFA\n";
    }
}
echo "\n";

echo "=== FIN TEST ===\n";
