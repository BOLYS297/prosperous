<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Perte extends Model
{
    protected $fillable = ['boutique_id', 'produit_id', 'user_id', 'quantite', 'raison', 'statut', 'photo_justificatif', 'admin_id', 'rejet_reason', 'validated_at'];

    public function boutique()
    {
        return $this->belongsTo(Boutique::class);
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
