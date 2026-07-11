<?php

namespace App\Jobs;

use App\Models\Depense;
use App\Models\Boutique;
use App\Notifications\AdminValidationNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class ApproveDepense implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Depense $depense, protected ?int $adminId = null) {}

    public function handle(): void
    {
        if ($this->depense->statut !== 'pending') {
            return;
        }

        $this->depense->update([
            'statut' => 'approved',
            'admin_id' => $this->adminId,
            'validated_at' => now(),
        ]);

        if ($this->depense->boutique_id) {
            $boutique = Boutique::find($this->depense->boutique_id);
            if ($boutique) {
                $boutique->decrement('solde', $this->depense->montant);
            }
        }

        if ($this->depense->user) {
            Notification::send($this->depense->user, new AdminValidationNotification(
                'Dépense validée',
                "Votre dépense de " . money_format_app($this->depense->montant) . " a été validée par l'administrateur.",
                'Voir la dépense',
                route('admin.rapports.index')
            ));
        }
    }
}
