<?php

namespace App\Support;

use App\Models\HoraireConnexion;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Tarification des heures supplémentaires.
 *
 * Les heures ne sont JAMAIS codées en dur : la référence est la SESSION
 * PRINCIPALE de l'employé, définie par l'admin dans « Tranches horaires » (par
 * rôle et par jour). Travailler EN DEHORS de cette session — arriver plus tôt ou
 * repartir plus tard — constitue des heures supplémentaires : le prix est
 * automatiquement majoré et la différence avec le prix normal revient à
 * l'employé qui réalise la vente, cumulée puis payée en fin de mois.
 */
class TarifHoraire
{
    /**
     * La vente de cet employé, à ce moment, tombe-t-elle en heures supplémentaires
     * (hors de sa session principale du jour) ?
     */
    public static function estMajore(User $user, ?Carbon $moment = null): bool
    {
        return HoraireConnexion::estHeuresSupp($user, $moment);
    }

    /**
     * Prix appliqué en heures supplémentaires.
     *
     * UNIQUEMENT le prix « heures supplémentaires » saisi par l'admin SUR LE
     * PRODUIT. Si aucun prix n'est défini pour cet article, le prix reste le prix
     * normal — jamais de majoration automatique par pourcentage (l'admin doit
     * décider article par article). Ne descend jamais sous le prix normal.
     */
    public static function prixMajore(Produit $produit, float $prixStandard): float
    {
        $base = $produit->getRawOriginal('prix_vente_hors_heures');

        if ($base !== null && (float) $base > 0) {
            return max($prixStandard, (float) $base);
        }

        return $prixStandard;
    }

    /** Session principale en cours pour cet employé (null s'il est en heures supp.). */
    public static function sessionCourante(User $user, ?Carbon $moment = null): ?HoraireConnexion
    {
        return HoraireConnexion::sessionAt($user, $moment);
    }

    /** Libellé d'une session : « 07:00 – 19:00 ». */
    public static function libellePlage(?HoraireConnexion $session): string
    {
        if (! $session) {
            return '—';
        }

        return substr($session->heure_debut, 0, 5) . ' – ' . substr($session->heure_fin, 0, 5);
    }
}
