<?php

namespace App\Support;

use App\Models\HoraireConnexion;
use App\Models\Produit;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Tarification des heures majorées.
 *
 * Les heures ne sont JAMAIS codées en dur : elles proviennent des tranches
 * horaires définies par l'admin (« Tranches horaires »), par rôle et par jour.
 * Chaque tranche porte un type :
 *   - normale : prix habituel ;
 *   - majoree : prix majoré. La différence avec le prix normal ne revient pas à
 *     l'entreprise mais à l'employé qui réalise la vente (heures supplémentaires),
 *     cumulée puis payée en fin de mois.
 */
class TarifHoraire
{
    /**
     * La vente de cet employé, à ce moment, tombe-t-elle dans une tranche majorée ?
     */
    public static function estMajore(User $user, ?Carbon $moment = null): bool
    {
        return HoraireConnexion::estMajoreeAt($user, $moment);
    }

    /**
     * Prix majoré d'un produit :
     *   1) prix « hors heures » saisi sur le produit ;
     *   2) sinon, prix normal + pourcentage global des Paramètres.
     * Ne descend jamais sous le prix normal.
     */
    public static function prixMajore(Produit $produit, float $prixStandard): float
    {
        $base = $produit->getRawOriginal('prix_vente_hors_heures');

        if ($base !== null && (float) $base > 0) {
            return max($prixStandard, (float) $base);
        }

        $pct = (float) Setting::get('majoration_hors_heures_percent', 0);
        if ($pct <= 0) {
            return $prixStandard;
        }

        return round($prixStandard * (1 + $pct / 100), 2);
    }

    /** Tranche en cours pour cet employé (pour l'affichage au point de vente). */
    public static function trancheCourante(User $user, ?Carbon $moment = null): ?HoraireConnexion
    {
        return HoraireConnexion::trancheAt($user, $moment);
    }

    /** Libellé d'une tranche : « 19:00 – 23:00 ». */
    public static function libellePlage(?HoraireConnexion $tranche): string
    {
        if (! $tranche) {
            return '—';
        }

        return substr($tranche->heure_debut, 0, 5) . ' – ' . substr($tranche->heure_fin, 0, 5);
    }
}
