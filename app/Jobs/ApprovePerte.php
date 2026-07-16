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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ApprovePerte implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Perte $perte, protected ?int $adminId = null) {}

    public function handle(): void
    {
        // Verrou + re-vérification du statut : une perte ne peut pas être validée
        // deux fois, et le stock ne peut pas être sorti deux fois.
        $sortie = DB::transaction(function () {
            $perte = Perte::where('id', $this->perte->id)->lockForUpdate()->first();

            if (! $perte || $perte->statut !== 'pending') {
                return false;
            }

            // Stock FIFO : le disponible est réparti sur PLUSIEURS lots. Lire un
            // seul lot (->first()) sous-estimait le stock et bloquait la
            // validation ; décrémenter ce seul lot laissait les autres intacts.
            if (Stock::totalFor($perte->boutique_id, $perte->produit_id) < $perte->quantite) {
                return false;
            }

            Stock::reduceQuantity($perte->boutique_id, $perte->produit_id, $perte->quantite);

            $perte->update([
                'statut' => 'approved',
                'admin_id' => $this->adminId,
                'validated_at' => now(),
            ]);

            return true;
        });

        if (! $sortie) {
            return;
        }

        $this->perte->refresh();

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
