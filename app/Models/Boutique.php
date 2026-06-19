<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Boutique extends Model
{
    protected $fillable = ['nom', 'type', 'solde'];

    public function ventes()
    {
        return $this->hasMany(Vente::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function paiements()
    {
        return $this->hasMany(AchatPaiement::class);
    }
}
