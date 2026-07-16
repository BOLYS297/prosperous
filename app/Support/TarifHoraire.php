<?php

namespace App\Support;

use App\Models\Produit;
use App\Models\Setting;
use Illuminate\Support\Carbon;

/**
 * Tarification hors heures d'ouverture.
 *
 * En dehors des heures d'ouverture (avant l'ouverture, après la fermeture), le
 * point de vente applique un prix MAJORÉ. La différence entre ce prix et le prix
 * normal ne revient pas à l'entreprise : elle est versée à l'employé qui réalise
 * la vente (heures supplémentaires), cumulée puis payée en fin de mois.
 */
class TarifHoraire
{
    /**
     * Le moment donné est-il hors des heures d'ouverture ?
     * L'ouverture et la fermeture sont INCLUSES dans les heures normales :
     * fermeture à 19:00 => la majoration démarre à 19:01.
     */
    public static function estHorsHeures(?Carbon $moment = null): bool
    {
        $moment = $moment ?: Carbon::now();

        $ouverture = self::heure('heure_ouverture', '07:00');
        $fermeture = self::heure('heure_fermeture', '19:00');

        $h = $moment->format('H:i');

        return $h < $ouverture || $h > $fermeture;
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

    /** Libellé des heures d'ouverture, pour l'affichage. */
    public static function plageOuverture(): string
    {
        return self::heure('heure_ouverture', '07:00') . ' – ' . self::heure('heure_fermeture', '19:00');
    }

    /** Normalise un réglage horaire au format H:i (tolère 7:00, 07:00:00...). */
    protected static function heure(string $cle, string $defaut): string
    {
        $valeur = trim((string) Setting::get($cle, $defaut));

        try {
            return Carbon::createFromFormat('H:i', substr($valeur, 0, 5))->format('H:i');
        } catch (\Throwable $e) {
            return $defaut;
        }
    }
}
