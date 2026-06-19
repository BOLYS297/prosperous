<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RechargeLigne extends Model
{
    protected $fillable = ['recharge_id', 'produit_id', 'quantite_envoyee', 'quantite_recue', 'quantite_manquante'];

    public function recharge()
    {
        return $this->belongsTo(Recharge::class);
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }
}
