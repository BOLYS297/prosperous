<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvanceCaisse extends Model
{
    protected $table = 'avances_caisse';

    protected $fillable = [
        'boutique_id',
        'admin_id',
        'montant',
        'montant_rembourse',
        'motif',
        'statut',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'montant_rembourse' => 'decimal:2',
    ];

    public function boutique()
    {
        return $this->belongsTo(Boutique::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function scopeEnCours($query)
    {
        return $query->where('statut', 'en_cours');
    }

    public function getResteARembourserAttribute(): float
    {
        return max(0, (float) $this->montant - (float) $this->montant_rembourse);
    }
}
