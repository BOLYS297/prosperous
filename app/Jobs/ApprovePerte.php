<?php

namespace App\Jobs;

use App\Models\Perte;
use App\Models\Stock;
use App\Notifications\AdminValidationNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class ApprovePerte implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Perte $perte, protected ?int $adminId = null) {}

    public function handle(): void
    {
        if ($this->perte->statut !== 'pending') {
            return;
        }

        $stock = Stock::where('boutique_id', $this->perte->boutique_id)
            ->where('produit_id', $this->perte->produit_id)
            ->first();

        if (!$stock || $stock->quantite < $this->perte->quantite) {
            return;
        }

        $stock->decrement('quantite', $this->perte->quantite);
        $this->perte->update([
            'statut' => 'approved',
            'admin_id' => $this->adminId,
            'validated_at' => now(),
        ]);

        if ($this->perte->user) {
            Notification::send($this->perte->user, new AdminValidationNotification(
                'Perte validée',
                "Votre perte de {$this->perte->quantite} unité(s) a été validée par l'administrateur.",
                'Voir la perte',
                route('admin.rapports.index')
            ));
        }
    }
}
