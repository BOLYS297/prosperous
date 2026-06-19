<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RechargeStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $title,
        protected string $message,
        protected string $actionLabel,
        protected string $actionUrl
    ) {}

    public function via($notifiable)
    {
        // Enregistrer en base et envoyer par mail
        return ['database', 'mail'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'action_label' => $this->actionLabel,
            'action_url' => $this->actionUrl,
        ];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->message)

            ->line('Merci de vérifier le statut de la recharge dans l’application.');
    }

    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }
}
