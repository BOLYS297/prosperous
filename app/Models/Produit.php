<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produit extends Model
{
    protected $fillable = ['nom', 'reference', 'prix_achat', 'prix_vente', 'image'];

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function prixGrossistes()
    {
        return $this->hasMany(PrixGrossiste::class);
    }
}
