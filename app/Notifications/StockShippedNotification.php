<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StockShippedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $productName,
        protected int $requestedQuantity,
        protected int $shippedQuantity,
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
            'title' => 'Stock expédié',
            'message' => "Votre demande de {$this->requestedQuantity} unité(s) de {$this->productName} a été expédiée avec {$this->shippedQuantity} unité(s) réellement envoyée(s).",
            'action_label' => 'Voir la demande',
            'action_url' => $this->requestUrl,
        ];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Votre stock a été expédié')
            ->line("Votre demande de {$this->requestedQuantity} unité(s) de {$this->productName} a été traitée.")
            ->line("Quantité demandée : {$this->requestedQuantity} unité(s)")
            ->line("Quantité réellement expédiée : {$this->shippedQuantity} unité(s)")
            ->line("Boutique : {$this->boutiqueName}")
            ->line('Merci de confirmer la réception lorsque vous aurez reçu le stock.');
    }

    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }
}
