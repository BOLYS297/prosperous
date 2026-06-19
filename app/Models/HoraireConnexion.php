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
