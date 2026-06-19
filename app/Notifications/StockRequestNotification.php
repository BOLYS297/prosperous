<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StockRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $productName,
        protected int $quantity,
        protected string $boutiqueName,
        protected string $requestUrl
    ) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Nouvelle demande de stock',
            'message' => "Une nouvelle demande de {$this->quantity} unité(s) de {$this->productName} a été créée par {$this->boutiqueName}.",
            'action_label' => 'Voir la demande',
            'action_url' => $this->requestUrl,
        ];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Nouvelle demande de stock')
            ->line("Une nouvelle demande de stock a été créée.")
            ->line("Boutique: {$this->boutiqueName}")
            ->line("Produit: {$this->productName}")
            ->line("Quantité: {$this->quantity} unité(s)")
            ->line('Merci de traiter cette demande dès que possible.');
    }

    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }
}
