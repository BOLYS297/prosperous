<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StockRequestProblemNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $productName,
        protected int $missingQuantity,
        protected string $boutiqueName,
        protected string $problemNote,
        protected string $requestUrl
    ) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Problème sur une demande de stock',
            'message' => "La boutique {$this->boutiqueName} a signalé un problème sur la demande de {$this->missingQuantity} unité(s) de {$this->productName}.",
            'details' => $this->problemNote,
            'action_label' => 'Voir la demande',
            'action_url' => $this->requestUrl,
        ];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Problème sur une demande de stock')
            ->line("Une demande de stock a rencontré un problème.")
            ->line("Boutique : {$this->boutiqueName}")
            ->line("Produit : {$this->productName}")
            ->line("Quantité manquante : {$this->missingQuantity} unité(s)")
            ->line('Message du boutiquier :')
            ->line($this->problemNote)
            ->line('Merci de renvoyer le stock manquant rapidement.');
    }

    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }
}
