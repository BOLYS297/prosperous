<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VenteLigne extends Model
{
    protected $fillable = [
        'vente_id',
        'produit_id',
        'quantite',
        'prix_unitaire',
        'prix_achat_unitaire',
        'commission_mecanicien',
        'est_grossiste',
    ];

    protected $casts = [
        'prix_unitaire' => 'decimal:2',
        'prix_achat_unitaire' => 'decimal:2',
        'commission_mecanicien' => 'decimal:2',
    ];

    /** Bénéfice de la ligne = (prix de vente - coût figé) x quantité. */
    public function getBeneficeAttribute(): float
    {
        return ((float) $this->prix_unitaire - (float) $this->prix_achat_unitaire) * (int) $this->quantite;
    }

    public function vente()
    {
        return $this->belongsTo(Vente::class);
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }
}
