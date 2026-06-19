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
        'statut',
        'note_probleme',
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
