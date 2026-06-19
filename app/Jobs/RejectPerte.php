<?php

namespace App\Jobs;

use App\Models\Perte;
use App\Notifications\AdminValidationNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class RejectPerte implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Perte $perte, protected ?int $adminId = null) {}

    public function handle(): void
    {
        if ($this->perte->statut !== 'pending') {
            return;
        }

        $this->perte->update([
            'statut' => 'rejected',
            'admin_id' => $this->adminId,
            'rejet_reason' => null,
            'validated_at' => now(),
        ]);

        if ($this->perte->user) {
            Notification::send($this->perte->user, new AdminValidationNotification(
                'Perte rejetée',
                "Votre perte de {$this->perte->quantite} unité(s) a été rejetée par l'administrateur.",
                'Voir la perte',
                route('admin.rapports.index')
            ));
        }
    }
}
