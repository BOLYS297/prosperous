<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebitValidation extends Model
{
    protected $fillable = [
        'boutique_id',
        'initiator_id',
        'responder_id',
        'amount',
        'source_type',
        'source_id',
        'motif',
        'status',
        'responded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'responded_at' => 'datetime',
    ];

    public function boutique()
    {
        return $this->belongsTo(Boutique::class);
    }

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function responder()
    {
        return $this->belongsTo(User::class, 'responder_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function getSourceLabelAttribute(): string
    {
        return match ($this->source_type) {
            'achat' => 'Achat comptant',
            'depense' => 'Dépense administrative',
            'recette' => 'Récupération de recette',
            default => $this->source_type,
        };
    }
}
