<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recharge extends Model
{
    protected $fillable = ['source_id', 'destination_id', 'user_id', 'montant', 'statut', 'fournisseur_id', 'raison_rejet', 'message_probleme', 'achat_id'];

    public function source()
    {
        return $this->belongsTo(Boutique::class, 'source_id');
    }

    public function destination()
    {
        return $this->belongsTo(Boutique::class, 'destination_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lignes()
    {
        return $this->hasMany(RechargeLigne::class);
    }

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_id');
    }

    public function justificatifs()
    {
        return $this->hasMany(RechargeJustificatif::class);
    }

    public function achat()
    {
        return $this->belongsTo(Achat::class);
    }
}
