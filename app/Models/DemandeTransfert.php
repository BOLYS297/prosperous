<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemandeTransfert extends Model
{
    protected $fillable = [
        'boutique_id',
        'produit_id',
        'quantite_demandee',
        'quantite_expediee',
        'quantite_recue',
        'prix_achat_unitaire',
        'prix_vente_unitaire',
        'prix_vente_grossiste_unitaire',
        'statut',
        'note_probleme',
    ];

    protected $casts = [
        'prix_achat_unitaire' => 'decimal:2',
        'prix_vente_unitaire' => 'decimal:2',
        'prix_vente_grossiste_unitaire' => 'decimal:2',
    ];

    public function boutique()
    {
        return $this->belongsTo(Boutique::class);
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }
}
