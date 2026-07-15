<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransfertStock extends Model
{
    protected $table = 'transferts_stock';

    protected $fillable = [
        'source_boutique_id',
        'destination_boutique_id',
        'produit_id',
        'initiator_id',
        'source_user_id',
        'destination_user_id',
        'quantite_demandee',
        'quantite_autorisee',
        'quantite_recue',
        'prix_achat_unitaire',
        'prix_vente_unitaire',
        'prix_vente_grossiste_unitaire',
        'statut',
        'note',
        'authorized_at',
        'received_at',
    ];

    protected $casts = [
        'quantite_demandee' => 'integer',
        'quantite_autorisee' => 'integer',
        'quantite_recue' => 'integer',
        'prix_achat_unitaire' => 'decimal:2',
        'prix_vente_unitaire' => 'decimal:2',
        'prix_vente_grossiste_unitaire' => 'decimal:2',
        'authorized_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public const STATUTS = [
        'en_attente_source' => 'En attente d\'autorisation',
        'autorise' => 'En transit (à réceptionner)',
        'recu' => 'Reçu',
        'refuse' => 'Refusé',
        'probleme' => 'Écart de réception',
    ];

    public function source()
    {
        return $this->belongsTo(Boutique::class, 'source_boutique_id');
    }

    public function destination()
    {
        return $this->belongsTo(Boutique::class, 'destination_boutique_id');
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function getStatutLabelAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    /** En attente d'autorisation par le vendeur de la boutique source. */
    public function scopeAAutoriser($query, int $boutiqueId)
    {
        return $query->where('source_boutique_id', $boutiqueId)->where('statut', 'en_attente_source');
    }

    /** Autorisé et en transit : à réceptionner par le vendeur destination. */
    public function scopeAReceptionner($query, int $boutiqueId)
    {
        return $query->where('destination_boutique_id', $boutiqueId)->where('statut', 'autorise');
    }
}
