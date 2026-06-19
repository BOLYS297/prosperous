<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vente extends Model
{
    use SoftDeletes;

    protected $fillable = ['boutique_id', 'user_id', 'montant_total', 'grossiste_id'];

    public function boutique()
    {
        return $this->belongsTo(Boutique::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function grossiste()
    {
        return $this->belongsTo(Grossiste::class);
    }

    public function lignes()
    {
        return $this->hasMany(VenteLigne::class);
    }
}
