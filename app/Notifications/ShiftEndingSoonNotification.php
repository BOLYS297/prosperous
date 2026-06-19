<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShiftEndingSoonNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $minutes;
    protected int $seconds;
    protected string $endTime;

    public function __construct(int $minutes, int $seconds, string $endTime)
    {
        $this->minutes = $minutes;
        $this->seconds = $seconds;
        $this->endTime = $endTime;
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Alerte : fin de votre tranche horaire bientôt')
            ->greeting('Bonjour ' . ($notifiable->nom_utilisateur ?? 'utilisateur'))
            ->line("Votre tranche horaire actuelle se termine à {$this->endTime}.")
            ->line("Il reste environ {$this->minutes} minute(s) et {$this->seconds} seconde(s).")
            ->line('Veuillez sauvegarder votre travail et préparer la clôture de votre session.')
            ->line('Merci d’utiliser Prosperous Motos.');
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "Votre tranche horaire se termine à {$this->endTime}. Il reste {$this->minutes} min {$this->seconds} s.",
            'end' => $this->endTime,
            'minutes' => $this->minutes,
            'seconds' => $this->seconds,
        ];
    }
}
