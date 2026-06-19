<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Commande;
use Illuminate\Support\Facades\Mail;

class SendDailyReports extends Command
{
    protected $signature = 'report:daily';
    protected $description = 'Envoie le rapport quotidien des ventes';

    public function handle()
    {
        $today = now()->toDateString();
        $commandes = Commande::whereDate('created_at', $today)->get();
        // construire rapport et envoyer mail à admin...
        $this->info('Rapport envoyé.');
    }
}
