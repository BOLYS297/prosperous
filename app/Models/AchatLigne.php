<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AchatLigne extends Model
{
    protected $fillable = ['achat_id', 'produit_id', 'quantite', 'prix_unitaire'];

    public function achat()
    {
        return $this->belongsTo(Achat::class);
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }
}
