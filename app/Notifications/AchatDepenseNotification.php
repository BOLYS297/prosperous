<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AchatDepenseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $boutiqueNom,
        protected float $montant,
        protected int $achatId,
        protected string $adminNom
    ) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => "Un montant de " . money_format_app($this->montant) . " a été imputé de votre solde pour une dépense admin.",
            'montant' => $this->montant,
        ];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Dépense administrative')
            ->line("Un montant de " . money_format_app($this->montant) . " a été imputé de votre solde pour une dépense admin.");
    }

    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }
}
