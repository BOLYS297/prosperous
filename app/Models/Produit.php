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

    public function getPrixAchatAttribute($value)
    {
        $activeStock = $this->stocks()
            ->where('quantite', '>', 0)
            ->orderBy('created_at')
            ->orderBy('id')
            ->first();

        if ($activeStock && $activeStock->prix_achat_unitaire !== null) {
            return (float) $activeStock->prix_achat_unitaire;
        }

        return $value;
    }

    public function getPrixVenteAttribute($value)
    {
        $activeStock = $this->stocks()
            ->where('quantite', '>', 0)
            ->orderBy('created_at')
            ->orderBy('id')
            ->first();

        if ($activeStock && $activeStock->prix_vente_unitaire !== null) {
            return (float) $activeStock->prix_vente_unitaire;
        }

        return $value;
    }

    /**
     * Prix de vente GROSSISTE du lot actif (le plus ancien en stock) — suit le FIFO.
     * Retourne null si aucun lot ou aucun prix grossiste défini.
     */
    public function getPrixVenteGrossisteAttribute()
    {
        $activeStock = $this->stocks()
            ->where('quantite', '>', 0)
            ->orderBy('created_at')
            ->orderBy('id')
            ->first();

        if ($activeStock && $activeStock->prix_vente_grossiste_unitaire !== null) {
            return (float) $activeStock->prix_vente_grossiste_unitaire;
        }

        return null;
    }
}
