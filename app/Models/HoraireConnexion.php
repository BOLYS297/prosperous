<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use App\Models\User;

class HoraireConnexion extends Model
{
    protected $fillable = [
        'role',
        'jour_semaine',
        'heure_debut',
        'heure_fin',
        'actif',
    ];

    /**
     * Session principale en cours pour cet employé à un instant donné : la
     * tranche définie par l'admin dans laquelle tombe ce moment. Null si le
     * moment est HORS session (avant le début ou après la fin = heures supp.).
     */
    public static function sessionAt(User $user, ?Carbon $moment = null): ?self
    {
        $moment = $moment ?: now();

        $jour = $moment->dayOfWeek - 1;
        $jour = $jour < 0 ? 6 : $jour;
        $heure = $moment->format('H:i:s');

        return $user->horaires()
            ->where('jour_semaine', $jour)
            ->where('actif', true)
            ->where('heure_debut', '<=', $heure)
            ->where('heure_fin', '>=', $heure)
            ->orderBy('heure_debut')
            ->first();
    }

    /**
     * L'employé a-t-il une session définie ce jour-là (à toute heure) ?
     * Sert de socle aux heures supplémentaires : on ne majore une vente hors
     * session que si l'employé était effectivement de service ce jour.
     */
    public static function aUneSessionCeJour(User $user, ?Carbon $moment = null): bool
    {
        $moment = $moment ?: now();

        $jour = $moment->dayOfWeek - 1;
        $jour = $jour < 0 ? 6 : $jour;

        return $user->horaires()
            ->where('jour_semaine', $jour)
            ->where('actif', true)
            ->exists();
    }

    /**
     * Ce moment correspond-il à des HEURES SUPPLÉMENTAIRES ?
     * Vrai si l'employé a une session ce jour, mais que le moment tombe EN DEHORS
     * de celle-ci — qu'il soit arrivé plus tôt (avant le début) ou reparti plus
     * tard (après la fin). La borne exacte du début et de la fin reste en heures
     * normales : la majoration démarre à la minute suivante.
     */
    public static function estHeuresSupp(User $user, ?Carbon $moment = null): bool
    {
        if (in_array($user->role, ['admin', 'super_admin'], true)) {
            return false;
        }

        if (! self::aUneSessionCeJour($user, $moment)) {
            return false;
        }

        // A une session ce jour ; heures supp. dès qu'on n'est dans aucune d'elles.
        return self::sessionAt($user, $moment) === null;
    }

    protected $casts = [
        'jour_semaine' => 'integer',
        'actif' => 'boolean',
    ];

    /**
     * L'utilisateur peut-il se connecter maintenant ?
     *
     * Les jours où il a une session définie, il peut se connecter À TOUTE HEURE :
     * cela lui permet d'arriver plus tôt ou de rester plus tard pour faire des
     * heures supplémentaires (majorées à son profit). Les jours SANS session
     * (repos), l'accès reste bloqué. La discipline reste tenue par les déductions
     * de retard / départ anticipé, calculées par rapport à la session principale.
     */
    public static function canUserConnect(User $user): bool
    {
        // Les admins et super admins peuvent toujours se connecter
        if (in_array($user->role, ['admin', 'super_admin'], true)) {
            return true;
        }

        if (app()->environment(['local', 'testing'])) {
            return true;
        }

        return self::aUneSessionCeJour($user);
    }

    public static function getCurrentIntervalForUser(User $user): ?self
    {
        if (in_array($user->role, ['admin', 'super_admin'], true)) {
            return null;
        }

        $currentDay = now()->dayOfWeek - 1;
        $currentDay = $currentDay < 0 ? 6 : $currentDay;
        $currentTime = now()->format('H:i:s');

        return $user->horaires()
            ->where('jour_semaine', $currentDay)
            ->where('actif', true)
            ->where('heure_debut', '<=', $currentTime)
            ->where('heure_fin', '>=', $currentTime)
            ->orderBy('heure_fin')
            ->first();
    }

    public static function getRemainingSecondsForUser(User $user): ?int
    {
        $interval = self::getCurrentIntervalForUser($user);
        if (! $interval) {
            return null;
        }

        try {
            $endTime = Carbon::parse(now()->toDateString() . ' ' . $interval->heure_fin);
        } catch (\Exception $e) {
            $endTime = Carbon::today()->setTimeFromTimeString($interval->heure_fin);
        }

        $remaining = $endTime->getTimestamp() - now()->getTimestamp();
        return $remaining > 0 ? $remaining : 0;
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'horaire_connexion_user');
    }

    /**
     * Récupère les tranches horaires pour un rôle
     */
    public static function forRole(string $role)
    {
        return self::where('role', $role)->orderBy('jour_semaine')->orderBy('heure_debut');
    }

    /**
     * Retourne le libellé du jour de la semaine
     */
    public function getDayLabel(): string
    {
        $days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        return $days[$this->jour_semaine] ?? 'Inconnu';
    }
}
