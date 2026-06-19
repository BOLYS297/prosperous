<?php
namespace App\Services;

class IAService
{
    public function suggestProducts($user, $context = [])
    {
        // placeholder: renvoie produits populaires. A remplacer par vrai modèle/requêtes.
        return \App\Models\Produit::orderByDesc('created_at')->limit(6)->get();
    }
}
