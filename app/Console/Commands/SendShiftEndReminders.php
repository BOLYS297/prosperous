<?php

namespace App\Console\Commands;

use App\Models\HoraireConnexion;
use App\Models\User;
use App\Notifications\ShiftEndingSoonNotification;
use Illuminate\Console\Command;

class SendShiftEndReminders extends Command
{
    protected $signature = 'shift:reminders';
    protected $description = 'Envoie par mail un avertissement aux utilisateurs avant la fin de leur tranche horaire.';

    public function handle()
    {
        $users = User::whereIn('role', ['magasinier', 'boutiquier'])
            ->whereNotNull('email')
            ->where('email', '<>', '')
            ->get();

        $sentCount = 0;

        foreach ($users as $user) {
            $remainingSeconds = HoraireConnexion::getRemainingSecondsForUser($user);
            if ($remainingSeconds === null || $remainingSeconds <= 0 || $remainingSeconds > 1800) {
                continue;
            }

            $alreadySent = $user->notifications()
                ->where('type', ShiftEndingSoonNotification::class)
                ->where('created_at', '>=', now()->subMinutes(30))
                ->exists();

            if ($alreadySent) {
                continue;
            }

            $interval = HoraireConnexion::getCurrentIntervalForUser($user);
            if (! $interval) {
                continue;
            }

            $user->notify(new ShiftEndingSoonNotification(
                floor($remainingSeconds / 60),
                $remainingSeconds % 60,
                $interval->heure_fin
            ));

            $sentCount++;
        }

        $this->info("Shift reminder emails envoyés : {$sentCount}");
    }
}
