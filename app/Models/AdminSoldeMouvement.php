<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdminSoldeMouvement extends Model
{
    protected $table = 'admin_solde_mouvements';

    protected $fillable = [
        'admin_id',
        'type',
        'montant',
        'motif',
        'boutique_id',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
    ];

    public const LIBELLES = [
        'recette' => 'Recette encaissée',
        'retrait' => 'Retrait',
        'achat' => 'Achat payé',
        'depense' => 'Dépense',
        'remboursement' => 'Remboursement de dette',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function boutique()
    {
        return $this->belongsTo(Boutique::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::LIBELLES[$this->type] ?? $this->type;
    }

    public function getEstEntreeAttribute(): bool
    {
        return (float) $this->montant >= 0;
    }

    /**
     * Enregistre un mouvement ET met à jour le solde personnel de l'admin de
     * façon atomique : le grand livre et le solde ne peuvent pas diverger.
     * $montant est SIGNÉ (+ = entrée, - = sortie).
     */
    public static function enregistrer(
        ?int $adminId,
        string $type,
        float $montant,
        ?string $motif = null,
        ?int $boutiqueId = null,
        ?string $sourceType = null,
        ?int $sourceId = null
    ): ?self {
        if (! $adminId) {
            return null;
        }

        return DB::transaction(function () use ($adminId, $type, $montant, $motif, $boutiqueId, $sourceType, $sourceId) {
            $admin = User::where('id', $adminId)->lockForUpdate()->first();
            if (! $admin) {
                return null;
            }

            if ($montant >= 0) {
                $admin->increment('solde_personnel', $montant);
            } else {
                $admin->decrement('solde_personnel', abs($montant));
            }

            return static::create([
                'admin_id' => $adminId,
                'type' => $type,
                'montant' => $montant,
                'motif' => $motif,
                'boutique_id' => $boutiqueId,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ]);
        });
    }
}
