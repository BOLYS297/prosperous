<?php

namespace App\Services;

use App\Models\Paiement;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PaiementService
{
    /**
     * Crée et gère un paiement.
     * Pour l'instant on supporte deux modes :
     * - 'simulation' : paiement instantané (reussi)
     * - sinon : retourne une URL de redirection simulée (simulate external gateway)
     *
     * @param array $data ['commande_id','montant','methode','user']
     * @return array ['status'=>'success'|'redirect'|'failed', 'url'=>..., 'paiement'=>Paiement|null]
     */
    public function createPayment(array $data)
    {
        // Si mode simulation (pour dev), on enregistre un paiement "réussi"
        if (($data['methode'] ?? '') === 'simulation') {
            $paiement = Paiement::create([
                'commande_id' => $data['commande_id'],
                'montant' => $data['montant'],
                'methode' => 'simulation',
                'reference' => 'SIM-' . strtoupper(Str::random(10)),
                'statut' => 'reussi',
                'details' => json_encode(['note' => 'Paiement simulé']),
                'date_paiement' => now()
            ]);

            return ['status' => 'success', 'paiement' => $paiement];
        }

        // Sinon on simule une redirection vers une gateway externe
        $fakeGatewayUrl = url('/paiement/simulate-gateway?cmd=' . $data['commande_id'] . '&m=' . $data['methode']);

        // On crée un enregistrement de paiement en attente
        $paiement = Paiement::create([
            'commande_id' => $data['commande_id'],
            'montant' => $data['montant'],
            'methode' => $data['methode'],
            'reference' => 'EXT-' . strtoupper(Str::random(12)),
            'statut' => 'en_attente',
            'details' => null,
            'date_paiement' => null
        ]);

        return ['status' => 'redirect', 'url' => $fakeGatewayUrl, 'paiement' => $paiement];
    }

    /**
     * Méthode appelée par le callback / webhook de la gateway
     * (ici simulée) pour marquer un paiement comme réussi ou échoué.
     */
    public function handleGatewayCallback(array $payload)
    {
        // $payload doit contenir : reference, status, transaction_id, etc.
        $paiement = Paiement::where('reference', $payload['reference'] ?? null)->first();
        if (! $paiement) {
            return false;
        }

        $paiement->statut = $payload['status'] === 'success' ? 'reussi' : 'echoue';
        $paiement->transaction_id = $payload['transaction_id'] ?? null;
        $paiement->details = json_encode($payload);
        $paiement->date_paiement = $payload['date_paiement'] ?? now();
        $paiement->save();

        // On peut ensuite mettre à jour la commande liée
        $commande = $paiement->commande;
        if ($paiement->statut === 'reussi') {
            $commande->update(['statut_commande' => 'en_traitement']); // ou 'payée'
        } else {
            $commande->update(['statut_commande' => 'annulée']);
        }

        return true;
    }
}
