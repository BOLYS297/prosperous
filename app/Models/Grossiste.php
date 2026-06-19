<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grossiste extends Model
{
    protected $fillable = ['nom', 'code', 'contact'];

    public function prixProduits()
    {
        return $this->hasMany(PrixGrossiste::class);
    }

    public function ventes()
    {
        return $this->hasMany(Vente::class);
    }

    public function ventesLignes()
    {
        return $this->hasMany(VenteLigne::class);
    }
}
