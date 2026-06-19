<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Boutique;
use App\Models\User;

echo "=== BOUTIQUES EN BASE ===\n";
$boutiques = Boutique::all();
foreach ($boutiques as $b) {
    echo "Boutique #{$b->id}: {$b->nom} (type: {$b->type}, solde: {$b->solde})\n";
}

echo "\n=== UTILISATEURS BOUTIQUIERS ===\n";
$boutiquiers = User::where('role', 'boutiquier')->get();
foreach ($boutiquiers as $u) {
    $boutique = $u->boutique;
    echo "User: {$u->nom_utilisateur} -> Boutique #{$u->boutique_id} ({$boutique->nom} - type: {$boutique->type})\n";
}
