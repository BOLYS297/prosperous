<?php

namespace App\Jobs;

use App\Models\Depense;
use App\Notifications\AdminValidationNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class RejectDepense implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Depense $depense, protected ?int $adminId = null) {}

    public function handle(): void
    {
        if ($this->depense->statut !== 'pending') {
            return;
        }

        $this->depense->update([
            'statut' => 'rejected',
            'admin_id' => $this->adminId,
            'rejet_reason' => null,
            'validated_at' => now(),
        ]);

        if ($this->depense->user) {
            Notification::send($this->depense->user, new AdminValidationNotification(
                'Dépense rejetée',
                "Votre dépense de " . money_format_app($this->depense->montant) . " a été rejetée par l'administrateur.",
                'Voir la dépense',
                route('admin.rapports.index')
            ));
        }
    }
}
