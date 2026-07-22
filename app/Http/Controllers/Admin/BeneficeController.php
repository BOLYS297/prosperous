<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use App\Models\VenteLigne;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BeneficeController extends Controller
{
    /**
     * Rapport des bénéfices : global et par point de vente, pour une journée.
     *
     * Le bénéfice n'est calculé que sur les lignes dont le COÛT a été figé à la
     * vente (vente_lignes.prix_achat_unitaire). Les ventes antérieures à cette
     * mise en place n'ont pas de coût : elles sont exclues du calcul et
     * signalées, plutôt que comptées comme 100 % de marge.
     */
    public function index(Request $request)
    {
        $date = $request->query('date')
            ? Carbon::parse($request->query('date'))->startOfDay()
            : Carbon::today();

        $parBoutique = $this->agreger($date, $date);
        $boutiques = Boutique::orderBy('type')->orderBy('nom')->get()->keyBy('id');

        // Détail des PIÈCES vendues ce jour, par point de vente : un clic sur la
        // ligne d'une boutique déplie la liste (quantité, CA, coût, bénéfice par
        // pièce). Mêmes règles que l'agrégat : coût figé requis pour le bénéfice.
        $piecesParBoutique = VenteLigne::query()
            ->join('ventes', 'ventes.id', '=', 'vente_lignes.vente_id')
            // leftJoin : une pièce supprimée du catalogue ne doit pas faire
            // disparaître ses ventes du détail.
            ->leftJoin('produits', 'produits.id', '=', 'vente_lignes.produit_id')
            ->whereNull('ventes.deleted_at')
            ->whereBetween('ventes.created_at', [(clone $date)->startOfDay(), (clone $date)->endOfDay()])
            ->selectRaw('ventes.boutique_id')
            ->selectRaw("COALESCE(produits.nom, 'Pièce supprimée') as produit_nom")
            ->selectRaw('produits.reference as produit_reference')
            ->selectRaw('SUM(vente_lignes.quantite) as quantite')
            ->selectRaw('SUM(vente_lignes.quantite * vente_lignes.prix_unitaire) as ca')
            ->selectRaw('SUM(CASE WHEN vente_lignes.prix_achat_unitaire IS NOT NULL THEN vente_lignes.quantite * vente_lignes.prix_unitaire ELSE 0 END) as ca_calculable')
            ->selectRaw('SUM(CASE WHEN vente_lignes.prix_achat_unitaire IS NOT NULL THEN vente_lignes.quantite * vente_lignes.prix_achat_unitaire ELSE 0 END) as cout')
            ->selectRaw('SUM(CASE WHEN vente_lignes.prix_achat_unitaire IS NULL THEN 1 ELSE 0 END) as lignes_sans_cout')
            ->groupBy('ventes.boutique_id', 'vente_lignes.produit_id', 'produits.nom', 'produits.reference')
            ->orderByRaw('SUM(vente_lignes.quantite * vente_lignes.prix_unitaire) DESC')
            ->get()
            ->groupBy('boutique_id');

        $global = [
            'ca_total' => $parBoutique->sum('ca_total'),
            'ca_calculable' => $parBoutique->sum('ca_calculable'),
            'cout' => $parBoutique->sum('cout'),
            'benefice' => $parBoutique->sum(fn ($r) => $r->ca_calculable - $r->cout),
            'lignes_sans_cout' => $parBoutique->sum('lignes_sans_cout'),
            'majoration_hors_heures' => $parBoutique->sum('majoration_hors_heures'),
            'nb_lignes' => $parBoutique->sum('nb_lignes'),
        ];

        // Tendance : bénéfice global des 7 derniers jours.
        $tendance = collect();
        for ($i = 6; $i >= 0; $i--) {
            $jour = (clone $date)->subDays($i);
            $agg = $this->agreger($jour, $jour);
            $tendance->push([
                'label' => $jour->translatedFormat('D d/m'),
                'benefice' => $agg->sum(fn ($r) => $r->ca_calculable - $r->cout),
            ]);
        }
        $maxTendance = max(1, $tendance->max('benefice'));

        return view('admin.benefices.index', compact('date', 'parBoutique', 'boutiques', 'global', 'tendance', 'maxTendance', 'piecesParBoutique'));
    }

    /** Agrégat des ventes par boutique entre deux dates (incluses). */
    protected function agreger(Carbon $debut, Carbon $fin)
    {
        return VenteLigne::query()
            ->join('ventes', 'ventes.id', '=', 'vente_lignes.vente_id')
            ->whereNull('ventes.deleted_at')
            ->whereBetween('ventes.created_at', [(clone $debut)->startOfDay(), (clone $fin)->endOfDay()])
            ->selectRaw('ventes.boutique_id')
            ->selectRaw('SUM(vente_lignes.quantite * vente_lignes.prix_unitaire) as ca_total')
            // Seules les lignes AVEC coût figé entrent dans le calcul du bénéfice.
            ->selectRaw('SUM(CASE WHEN vente_lignes.prix_achat_unitaire IS NOT NULL THEN vente_lignes.quantite * vente_lignes.prix_unitaire ELSE 0 END) as ca_calculable')
            ->selectRaw('SUM(CASE WHEN vente_lignes.prix_achat_unitaire IS NOT NULL THEN vente_lignes.quantite * vente_lignes.prix_achat_unitaire ELSE 0 END) as cout')
            ->selectRaw('SUM(CASE WHEN vente_lignes.prix_achat_unitaire IS NULL THEN 1 ELSE 0 END) as lignes_sans_cout')
            // Majoration hors heures : encaissée par l'entreprise puis reversée à
            // l'employé en prime -> à isoler pour ne pas gonfler la marge réelle.
            ->selectRaw('SUM(COALESCE(vente_lignes.prime_employe, 0)) as majoration_hors_heures')
            ->selectRaw('COUNT(*) as nb_lignes')
            ->groupBy('ventes.boutique_id')
            ->get();
    }
}
