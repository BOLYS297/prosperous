<?php

namespace App\Console\Commands;

use App\Models\Grossiste;
use Illuminate\Console\Command;

class SyncGrossisteDefaultPrices extends Command
{
    protected $signature = 'grossistes:sync-default-prices';

    protected $description = 'Applique le prix grossiste par défaut de tous les produits aux grossistes qui n\'en ont pas encore (n\'écrase aucun tarif personnalisé).';

    public function handle(): int
    {
        $grossistes = Grossiste::all();

        if ($grossistes->isEmpty()) {
            $this->info('Aucun grossiste à traiter.');

            return self::SUCCESS;
        }

        $total = 0;
        foreach ($grossistes as $grossiste) {
            $created = $grossiste->syncDefaultPrices();
            $total += $created;
            $this->line("• {$grossiste->nom} : {$created} tarif(s) par défaut ajouté(s).");
        }

        $this->info("Terminé. {$total} tarif(s) créé(s) au total.");

        return self::SUCCESS;
    }
}
