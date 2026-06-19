<?php
namespace App\Services;

class LivraisonService
{
    public function calculateFees($adresse, $poids = 0, $valeur = 0)
    {
        // exemple simple : frais fixes + frais selon poids
        $base = 1500;
        $byKg = max(0, $poids - 1) * 500;
        return $base + $byKg;
    }
}
