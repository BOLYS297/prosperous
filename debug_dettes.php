<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Achat;
use App\Models\User;

$user = User::where('role', 'boutiquier')->first();
if (!$user) {
    die("Aucun boutiquier trouvé\n");
}

$boutique = $user->boutique;
echo "=== DIAGNOSTIC DETTES ===\n";
echo "Boutiquier: {$user->nom_utilisateur}\n";
echo "Boutique: {$boutique->nom} (ID: {$boutique->id})\n";
echo "\n";

// 1. Toutes les dettes
echo "1. TOUTES LES DETTES EN BASE:\n";
$toutesLesDettesBrutes = Achat::where('statut', 'dette')->get();
echo "Nombre total: {$toutesLesDettesBrutes->count()}\n";
foreach ($toutesLesDettesBrutes as $achat) {
    echo "  - Achat #{$achat->id}: boutique_id={$achat->boutique_id}, montant={$achat->montant_total}, reste={$achat->reste_a_payer}\n";
    if ($achat->recharge) {
        echo "    Recharge: destination_id={$achat->recharge->destination_id}\n";
    }
}
echo "\n";

// 2. Dettes de la boutique (ancien filtre)
echo "2. DETTES AVEC ANCIEN FILTRE (boutique_id directement):\n";
$dettesAncien = Achat::where('statut', 'dette')
    ->where('boutique_id', $boutique->id)
    ->get();
echo "Nombre: {$dettesAncien->count()}\n";
foreach ($dettesAncien as $achat) {
    echo "  - Achat #{$achat->id}: montant={$achat->montant_total}, reste={$achat->reste_a_payer}\n";
}
echo "\n";

// 3. Dettes via recharge
echo "3. DETTES VIA RECHARGE (recharge.destination_id):\n";
$dettesRecharge = Achat::where('statut', 'dette')
    ->whereHas('recharge', function ($query) use ($boutique) {
        $query->where('destination_id', $boutique->id);
    })
    ->get();
echo "Nombre: {$dettesRecharge->count()}\n";
foreach ($dettesRecharge as $achat) {
    echo "  - Achat #{$achat->id}: boutique_id={$achat->boutique_id}, montant={$achat->montant_total}, reste={$achat->reste_a_payer}\n";
    if ($achat->recharge) {
        echo "    Recharge: destination_id={$achat->recharge->destination_id}\n";
    }
}
echo "\n";

// 4. Dettes avec le filtre combiné (nouveau)
echo "4. DETTES AVEC NOUVEAU FILTRE (combiné OR):\n";
$dettesNouveau = Achat::with('paiements')
    ->where('statut', 'dette')
    ->where(function ($query) use ($boutique) {
        $query->where('boutique_id', $boutique->id)
            ->orWhereHas('recharge', function ($query) use ($boutique) {
                $query->where('destination_id', $boutique->id);
            });
    })
    ->get();
echo "Nombre: {$dettesNouveau->count()}\n";
foreach ($dettesNouveau as $achat) {
    echo "  - Achat #{$achat->id}: boutique_id={$achat->boutique_id}, montant={$achat->montant_total}, reste={$achat->reste_a_payer}\n";
    if ($achat->recharge) {
        echo "    Recharge: destination_id={$achat->recharge->destination_id}\n";
    }
}
echo "\n";

// 5. Dettes filtrées avec reste_a_payer > 0
echo "5. DETTES AVEC NOUVEAU FILTRE + reste_a_payer > 0:\n";
$dettesFinales = $dettesNouveau->filter(fn($achat) => $achat->reste_a_payer > 0);
echo "Nombre: {$dettesFinales->count()}\n";
foreach ($dettesFinales as $achat) {
    echo "  - Achat #{$achat->id}: montant={$achat->montant_total}, montant_paye={$achat->montant_paye}, reste={$achat->reste_a_payer}\n";
}
echo "\n";

echo "=== FIN DIAGNOSTIC ===\n";
