<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$boutiqueId = DB::table('users')->where('role', 'magasinier')->value('boutique_id');
echo "boutiqueId={$boutiqueId}\n";

// Method A: whereDoesntHave + whereHas (as in controller)
$countA = DB::table('produits')
    ->whereNotExists(function ($q) use ($boutiqueId) {
        $q->select(DB::raw(1))
            ->from('stocks')
            ->whereRaw('stocks.produit_id = produits.id')
            ->where('boutique_id', $boutiqueId)
            ->where('quantite', '>', 0);
    })
    ->whereExists(function ($q) use ($boutiqueId) {
        $q->select(DB::raw(1))
            ->from('stocks')
            ->whereRaw('stocks.produit_id = produits.id')
            ->where('boutique_id', $boutiqueId);
    })
    ->count();

echo "ruptures_methodA={$countA}\n";

// Method B: group by produit_id sum quantite
$rows = DB::table('stocks')
    ->select('produit_id', DB::raw('SUM(COALESCE(quantite,0)) as total'))
    ->where('boutique_id', $boutiqueId)
    ->groupBy('produit_id')
    ->havingRaw('SUM(COALESCE(quantite,0)) <= 0')
    ->get();

echo "ruptures_methodB=" . count($rows) . "\n";

// Display up to 20 products that are in rupture per method B
if (count($rows) > 0) {
    echo "Products in rupture (methodB):\n";
    foreach ($rows as $r) {
        $p = DB::table('produits')->where('id', $r->produit_id)->first();
        echo "- id={$r->produit_id} name=" . ($p->nom ?? 'n/a') . " ref=" . ($p->reference ?? 'n/a') . " total=" . $r->total . "\n";
    }
}

// Check products shown in magasinier stocks view (where sum of stocks maybe zero)
$productsView = DB::table('produits')
    ->join('stocks', 'produits.id', '=', 'stocks.produit_id')
    ->where('stocks.boutique_id', $boutiqueId)
    ->select('produits.id', 'produits.nom', 'produits.reference', DB::raw('SUM(COALESCE(stocks.quantite,0)) as total'))
    ->groupBy('produits.id', 'produits.nom', 'produits.reference')
    ->orderBy('produits.nom')
    ->get();

echo "\nSample of products with totals:\n";
foreach ($productsView as $pv) {
    echo "id={$pv->id} name={$pv->nom} ref={$pv->reference} total={$pv->total}\n";
}

// Also check if any stock rows for boutique have quantite <=0
$stockRows = DB::table('stocks')->where('boutique_id', $boutiqueId)->where('quantite', '<=', 0)->get();
echo "\nstock_rows_with_quantite_le_0=" . count($stockRows) . "\n";

if (count($stockRows) > 0) {
    echo "Sample stock rows with quantite <=0:\n";
    foreach (array_slice($stockRows->toArray(), 0, 20) as $s) {
        echo "stock_id={$s->id} produit_id={$s->produit_id} quantite={$s->quantite} created_at={$s->created_at}\n";
    }
}

echo "\nDone.\n";
