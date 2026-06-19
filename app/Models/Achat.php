<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Achat extends Model
{
    protected $fillable = ['fournisseur_id', 'boutique_id', 'debit_boutique_id', 'montant_total', 'statut'];

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function boutique()
    {
        return $this->belongsTo(Boutique::class);
    }

    public function debitBoutique()
    {
        return $this->belongsTo(Boutique::class, 'debit_boutique_id');
    }

    public function lignes()
    {
        return $this->hasMany(AchatLigne::class);
    }

    public function paiements()
    {
        return $this->hasMany(AchatPaiement::class);
    }

    public function getMontantPayeAttribute()
    {
        if ($this->relationLoaded('paiements')) {
            return $this->paiements->sum('montant');
        }

        return $this->paiements()->sum('montant');
    }

    public function getResteAPayerAttribute()
    {
        return max(0, $this->montant_total - $this->montant_paye);
    }

    public function recharge()
    {
        return $this->hasOne(Recharge::class);
    }
}
