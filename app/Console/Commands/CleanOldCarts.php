<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Panier;
use Carbon\Carbon;

class CleanOldCarts extends Command
{
    protected $signature = 'cart:clean';
    protected $description = 'Supprime les paniers inactifs depuis 30 jours';

    public function handle()
    {
        $date = now()->subDays(30);
        $count = Panier::where('status','actif')->where('updated_at','<',$date)->delete();
        $this->info("Paniers supprimés: $count");
    }
}
