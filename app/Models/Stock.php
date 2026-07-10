<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = [
        'boutique_id',
        'produit_id',
        'quantite',
        'prix_achat_unitaire',
        'prix_vente_unitaire',
        'prix_vente_grossiste_unitaire',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'prix_achat_unitaire' => 'decimal:2',
        'prix_vente_unitaire' => 'decimal:2',
        'prix_vente_grossiste_unitaire' => 'decimal:2',
    ];

    public function boutique()
    {
        return $this->belongsTo(Boutique::class);
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }

    public static function addBatch(int $boutiqueId, int $produitId, int $quantite, ?float $prixAchat = null, ?float $prixVente = null, ?float $prixVenteGrossiste = null, ?string $sourceType = null, ?int $sourceId = null): self
    {
        return static::create([
            'boutique_id' => $boutiqueId,
            'produit_id' => $produitId,
            'quantite' => $quantite,
            'prix_achat_unitaire' => $prixAchat,
            'prix_vente_unitaire' => $prixVente,
            'prix_vente_grossiste_unitaire' => $prixVenteGrossiste,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ]);
    }

    public static function getAvailableStocks(int $boutiqueId, int $produitId)
    {
        return static::where('boutique_id', $boutiqueId)
            ->where('produit_id', $produitId)
            ->where('quantite', '>', 0)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    public static function consumeForSale(int $boutiqueId, int $produitId, int $quantite, ?float $fallbackSalePrice = null): array
    {
        $stocks = static::where('boutique_id', $boutiqueId)
            ->where('produit_id', $produitId)
            ->where('quantite', '>', 0)
            ->orderBy('created_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $remaining = $quantite;
        $consumed = [];

        foreach ($stocks as $stock) {
            if ($remaining <= 0) {
                break;
            }

            if ($stock->quantite <= 0) {
                continue;
            }

            $take = min($stock->quantite, $remaining);
            $stock->decrement('quantite', $take);
            $remaining -= $take;

            $consumed[] = [
                'stock' => $stock,
                'quantite' => $take,
                'prix_unitaire' => $stock->prix_vente_unitaire ?? $fallbackSalePrice,
                'prix_grossiste' => $stock->prix_vente_grossiste_unitaire,
            ];
        }

        if ($remaining > 0) {
            throw new \RuntimeException('Stock insuffisant pour cette vente.');
        }

        return $consumed;
    }

    public static function restoreQuantity(int $boutiqueId, int $produitId, int $quantite): self
    {
        $stock = static::where('boutique_id', $boutiqueId)
            ->where('produit_id', $produitId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if ($stock) {
            $stock->increment('quantite', $quantite);
            return $stock;
        }

        return static::create([
            'boutique_id' => $boutiqueId,
            'produit_id' => $produitId,
            'quantite' => $quantite,
        ]);
    }
}
