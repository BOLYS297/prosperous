<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produit extends Model
{
    protected $fillable = ['nom', 'reference', 'prix_achat', 'prix_vente', 'prix_vente_grossiste', 'prix_vente_hors_heures', 'image'];

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
     * Prix de vente GROSSISTE par défaut du produit.
     * Priorité : valeur définie sur le produit (colonne), sinon prix grossiste
     * du lot actif le plus ancien (défini à l'achat). Null si rien n'est défini.
     */
    public function getPrixVenteGrossisteAttribute($value)
    {
        if ($value !== null && $value !== '') {
            return (float) $value;
        }

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
