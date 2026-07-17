<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Achat extends Model
{
    protected $fillable = ['fournisseur_id', 'boutique_id', 'debit_boutique_id', 'debit_admin_id', 'montant_total', 'statut'];

    /** Dette imputée au solde personnel d'un administrateur (et non à une boutique). */
    public function debitAdmin()
    {
        return $this->belongsTo(User::class, 'debit_admin_id');
    }

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
        // Arrondi à l'unité : le FCFA n'a pas de subdivision utilisée en
        // pratique, et un résidu de quelques centimes (venant d'un prix
        // unitaire à décimales) empêcherait cette dette de jamais se solder,
        // malgré un "Restant : 0 FCFA" affiché (arrondi, lui, à l'affichage).
        return round(max(0, $this->montant_total - $this->montant_paye));
    }

    public function recharge()
    {
        return $this->hasOne(Recharge::class);
    }
}
