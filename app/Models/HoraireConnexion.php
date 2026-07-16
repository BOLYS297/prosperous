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
        'type',
        'actif',
    ];

    public const TYPE_NORMALE = 'normale';
    public const TYPE_MAJOREE = 'majoree';

    /**
     * Tranche horaire applicable à un employé à un instant donné.
     * En cas de chevauchement, la tranche qui commence le plus tôt l'emporte :
     * avec 07:00-19:00 (normale) et 19:00-23:00 (majorée), 19:00 reste au tarif
     * normal et la majoration démarre à 19:01.
     */
    public static function trancheAt(User $user, ?Carbon $moment = null): ?self
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
     * L'employé se trouve-t-il dans une tranche à tarif MAJORÉ ?
     * Hors de toute tranche, on n'applique aucune majoration : seules les plages
     * explicitement marquées « majorée » par l'admin la déclenchent.
     */
    public static function estMajoreeAt(User $user, ?Carbon $moment = null): bool
    {
        return optional(self::trancheAt($user, $moment))->type === self::TYPE_MAJOREE;
    }

    public function estMajoree(): bool
    {
        return $this->type === self::TYPE_MAJOREE;
    }

    protected $casts = [
        'jour_semaine' => 'integer',
        'actif' => 'boolean',
    ];

    /**
     * Vérifie si un utilisateur peut se connecter maintenant
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

        $currentDay = now()->dayOfWeek - 1; // dayOfWeek retourne 1=dimanche, on veut 0=lundi
        $currentDay = $currentDay < 0 ? 6 : $currentDay;
        $currentTime = now()->format('H:i:s');

        return $user->horaires()
            ->where('jour_semaine', $currentDay)
            ->where('actif', true)
            ->where('heure_debut', '<=', $currentTime)
            ->where('heure_fin', '>=', $currentTime)
            ->exists();
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
