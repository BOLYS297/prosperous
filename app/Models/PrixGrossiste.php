<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrixGrossiste extends Model
{
    protected $fillable = ['grossiste_id', 'produit_id', 'prix_achat', 'prix_vente'];
    public $timestamps = false;

    public function grossiste()
    {
        return $this->belongsTo(Grossiste::class);
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }
}
