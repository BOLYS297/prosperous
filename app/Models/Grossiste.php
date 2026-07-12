<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grossiste extends Model
{
    protected $fillable = ['nom', 'code', 'contact'];

    public function prixProduits()
    {
        return $this->hasMany(PrixGrossiste::class);
    }

    public function ventes()
    {
        return $this->hasMany(Vente::class);
    }

    public function ventesLignes()
    {
        return $this->hasMany(VenteLigne::class);
    }

    /**
     * Prix grossiste par défaut de chaque produit :
     *   prix grossiste du lot actif le plus ancien, sinon le prix de vente client.
     * Utilise les colonnes brutes (getRawOriginal) pour éviter les accessors N+1.
     *
     * @return array<int, float>  [produit_id => prix_vente_grossiste_par_defaut]
     */
    public static function defaultPriceMap(): array
    {
        $lotGrossiste = Stock::query()
            ->where('quantite', '>', 0)
            ->whereNotNull('prix_vente_grossiste_unitaire')
            ->where('prix_vente_grossiste_unitaire', '>', 0)
            ->orderBy('created_at')
            ->get(['produit_id', 'prix_vente_grossiste_unitaire'])
            ->groupBy('produit_id')
            ->map(fn ($lots) => (float) $lots->first()->prix_vente_grossiste_unitaire);

        $map = [];
        foreach (Produit::all() as $produit) {
            $map[$produit->id] = $lotGrossiste[$produit->id]
                ?? (float) ($produit->getRawOriginal('prix_vente') ?? 0);
        }

        return $map;
    }

    /**
     * Crée les tarifs par défaut pour tous les produits qui n'en ont pas encore
     * pour ce grossiste (n'écrase jamais un tarif déjà personnalisé).
     * Retourne le nombre de lignes créées.
     */
    public function syncDefaultPrices(): int
    {
        $defaults = static::defaultPriceMap();
        $existing = array_flip($this->prixProduits()->pluck('produit_id')->all());
        $rows = [];

        // La table prix_grossistes n'a pas de timestamps ($timestamps = false).
        foreach (Produit::all(['id', 'prix_achat']) as $produit) {
            if (isset($existing[$produit->id])) {
                continue;
            }

            $rows[] = [
                'grossiste_id' => $this->id,
                'produit_id' => $produit->id,
                'prix_achat' => (float) ($produit->getRawOriginal('prix_achat') ?? 0),
                'prix_vente' => $defaults[$produit->id] ?? 0,
            ];
        }

        if (! empty($rows)) {
            PrixGrossiste::insert($rows);
        }

        return count($rows);
    }
}
