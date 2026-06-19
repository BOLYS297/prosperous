<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AchatPaiement extends Model
{
    protected $fillable = ['achat_id', 'boutique_id', 'user_id', 'montant', 'description'];

    public function achat()
    {
        return $this->belongsTo(Achat::class);
    }

    public function boutique()
    {
        return $this->belongsTo(Boutique::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
