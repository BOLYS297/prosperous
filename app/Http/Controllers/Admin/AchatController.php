<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Notifications\AchatDepenseNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class AchatController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->query('q', ''));

        $achats = \App\Models\Achat::with(['fournisseur', 'boutique', 'lignes.produit', 'recharge.lignes'])
            ->when($q, function ($query) use ($q) {
                $query->where(function ($query) use ($q) {
                    $query->where('id', 'like', "%{$q}%")
                        ->orWhere('montant_total', 'like', "%{$q}%")
                        ->orWhere('statut', 'like', "%{$q}%")
                        ->orWhereHas('fournisseur', function ($query) use ($q) {
                            $query->where('nom', 'like', "%{$q}%");
                        })
                        ->orWhereHas('boutique', function ($query) use ($q) {
                            $query->where('nom', 'like', "%{$q}%");
                        })
                        ->orWhereHas('lignes.produit', function ($query) use ($q) {
                            $query->where('nom', 'like', "%{$q}%")
                                ->orWhere('reference', 'like', "%{$q}%");
                        });
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // État de validation du paiement comptant par la boutique, par achat.
        $achatValidations = \App\Models\DebitValidation::where('source_type', 'achat')
            ->whereIn('source_id', $achats->pluck('id'))
            ->get()
            ->keyBy('source_id');

        return view('admin.achats.index', compact('achats', 'q', 'achatValidations'));
    }

    public function create()
    {
        $fournisseurs = \App\Models\Fournisseur::all();
        $produits = \App\Models\Produit::all();
        // Destination finale de tout achat admin : le magasin uniquement.
        $magasins = \App\Models\Boutique::where('type', 'magasin')->get();
        // Liste complète des boutiques utilisables pour le débit (trésorerie)
        $allBoutiques = \App\Models\Boutique::orderBy('nom')->get();

        return view('admin.achats.create', compact('fournisseurs', 'produits', 'magasins', 'allBoutiques'));
    }

    public function store(Request $request)
    {
        $rules = [
            'fournisseur_id' => 'required|exists:fournisseurs,id',
            'boutique_id' => [
                'required',
                'exists:boutiques,id',
                function ($attribute, $value, $fail) {
                    $boutique = \App\Models\Boutique::find($value);
                    if (! $boutique || $boutique->type !== 'magasin') {
                        $fail('La destination doit être un magasin.');
                    }
                },
            ],
            'statut' => 'required|in:paye,dette',
            'lignes' => 'required|array|min:1',
            'lignes.*.produit_id' => 'required|exists:produits,id',
            'lignes.*.quantite' => 'required|integer|min:1',
            'lignes.*.prix_unitaire' => 'required|numeric|min:0',
            'lignes.*.prix_vente' => 'nullable|numeric|min:0',
            'lignes.*.prix_vente_grossiste' => 'nullable|numeric|min:0',
        ];

        // Make debit_boutique_id required only when statut === 'paye'.
        if ($request->input('statut') === 'paye') {
            $rules['debit_boutique_id'] = 'required|exists:boutiques,id';
        } else {
            // Pour une dette : boutique responsable OPTIONNELLE (null = dette partagée par toutes).
            $rules['debit_boutique_id'] = 'nullable|exists:boutiques,id';
        }

        $request->validate($rules);

        DB::transaction(function () use ($request) {
            $montant_total = 0;

            foreach ($request->lignes as $ligne) {
                $montant_total += $ligne['quantite'] * $ligne['prix_unitaire'];
            }

            $achat = \App\Models\Achat::create([
                'fournisseur_id' => $request->fournisseur_id,
                'boutique_id' => $request->boutique_id,
                'debit_boutique_id' => $request->filled('debit_boutique_id') ? $request->debit_boutique_id : null,
                'statut' => $request->statut,
                'montant_total' => $montant_total
            ]);

            $destination = \App\Models\Boutique::find($request->boutique_id);

            foreach ($request->lignes as $ligne) {
                \App\Models\AchatLigne::create([
                    'achat_id' => $achat->id,
                    'produit_id' => $ligne['produit_id'],
                    'quantite' => $ligne['quantite'],
                    'prix_unitaire' => $ligne['prix_unitaire'],
                    'prix_vente' => isset($ligne['prix_vente']) && $ligne['prix_vente'] !== '' ? $ligne['prix_vente'] : null,
                    'prix_vente_grossiste' => isset($ligne['prix_vente_grossiste']) && $ligne['prix_vente_grossiste'] !== '' ? $ligne['prix_vente_grossiste'] : null,
                ]);

                // Mettre à jour les prix du produit (prix d'achat et prix de vente) en base
                try {
                    $produit = \App\Models\Produit::find($ligne['produit_id']);
                    if ($produit) {
                        $produit->prix_achat = $ligne['prix_unitaire'];
                        if (isset($ligne['prix_vente']) && $ligne['prix_vente'] !== null && $ligne['prix_vente'] !== '') {
                            $produit->prix_vente = $ligne['prix_vente'];
                        }
                        if (isset($ligne['prix_vente_grossiste']) && $ligne['prix_vente_grossiste'] !== null && $ligne['prix_vente_grossiste'] !== '') {
                            $produit->prix_vente_grossiste = $ligne['prix_vente_grossiste'];
                        }
                        $produit->save();
                    }
                } catch (\Throwable $e) {
                    Log::warning('Impossible de mettre à jour les prix du produit pour l\'achat: ' . $e->getMessage());
                }

                // If destination is not a magasin, increment stock immediately.
                if (! $destination || $destination->type !== 'magasin') {
                    $batchPrixVente = isset($ligne['prix_vente']) && $ligne['prix_vente'] !== null && $ligne['prix_vente'] !== ''
                        ? $ligne['prix_vente']
                        : $ligne['prix_unitaire'];

                    $batchPrixGrossiste = isset($ligne['prix_vente_grossiste']) && $ligne['prix_vente_grossiste'] !== '' ? $ligne['prix_vente_grossiste'] : null;

                    \App\Models\Stock::addBatch(
                        $request->boutique_id,
                        $ligne['produit_id'],
                        $ligne['quantite'],
                        $ligne['prix_unitaire'],
                        $batchPrixVente,
                        $batchPrixGrossiste,
                        'achat',
                        $achat->id
                    );
                }
            }

            // Si c'est payé comptant, on ne débite PAS immédiatement : on crée une
            // demande de validation. Le solde de la boutique ne sera débité (et le
            // paiement enregistré) qu'après confirmation par le boutiquier, qui peut
            // aussi contester le débit.
            $debitBoutique = $request->input('statut') === 'paye' ? \App\Models\Boutique::find($request->debit_boutique_id) : null;

            if ($request->statut === 'paye' && $debitBoutique) {
                \App\Models\DebitValidation::create([
                    'boutique_id' => $debitBoutique->id,
                    'initiator_id' => Auth::id(),
                    'amount' => $montant_total,
                    'source_type' => 'achat',
                    'source_id' => $achat->id,
                    'motif' => "Paiement comptant de l'achat #{$achat->id}",
                    'status' => 'pending',
                ]);

                // Notifier le(s) boutiquier(s) de la boutique débitée pour validation.
                $this->notifyBoutiquiersForValidation($debitBoutique, $montant_total, "l'achat #{$achat->id}");
            } elseif ($request->statut === 'dette') {
                // Aucun avis de débit de caisse ne doit être envoyé aux boutiques
                // pour un achat admin à crédit.
            }

            // If destination is a magasin, always create a Recharge record for magasinier validation
            if ($destination && $destination->type === 'magasin') {
                $recharge = \App\Models\Recharge::create([
                    'source_id' => null,
                    'destination_id' => $request->boutique_id,
                    'user_id' => \Illuminate\Support\Facades\Auth::id(),
                    'montant' => $montant_total,
                    'statut' => 'en_attente',
                    'fournisseur_id' => $request->fournisseur_id,
                    'achat_id' => $achat->id,
                ]);

                foreach ($request->lignes as $ligne) {
                    \App\Models\RechargeLigne::create([
                        'recharge_id' => $recharge->id,
                        'produit_id' => $ligne['produit_id'],
                        'quantite_envoyee' => $ligne['quantite'],
                        'quantite_recue' => 0,
                        'quantite_manquante' => $ligne['quantite'],
                    ]);
                }

                // Notifier le(s) magasinier(s) de la boutique destination pour validation
                try {
                    $currentHour = (int) now()->format('H');
                    $shift = null;
                    if ($currentHour >= 7 && $currentHour < 17) {
                        $shift = 'matin';
                    } elseif ($currentHour >= 17 && $currentHour < 23) {
                        $shift = 'soir';
                    }

                    $userQuery = \App\Models\User::where('role', 'magasinier')->where('boutique_id', $destination->id);
                    $presentMagasiniers = $shift ? (clone $userQuery)->where('shift', $shift)->get() : collect();
                    $magasinierRecipients = $presentMagasiniers->isNotEmpty() ? $presentMagasiniers : $userQuery->get();

                    if ($magasinierRecipients->isNotEmpty()) {
                        $title = 'Nouvelle recharge en attente';
                        $message = "Une nouvelle recharge (Achat #{$achat->id}) est en attente de validation pour la boutique {$destination->nom}. Merci de confirmer la réception ou de signaler une anomalie.";
                        $actionUrl = route('magasinier.recharges.show', $recharge->id);

                        Notification::send($magasinierRecipients, new \App\Notifications\RechargeStatusNotification(
                            $title,
                            $message,
                            'Voir la recharge',
                            $actionUrl
                        ));
                    }
                } catch (\Throwable $e) {
                    // Ne pas faire échouer la transaction si la notification pose problème; loggons l'erreur.
                    Log::error('Erreur en notifiant les magasiniers pour la recharge: ' . $e->getMessage());
                }
            }
        });

        $message = $request->statut === 'paye'
            ? 'Achat enregistré. Le paiement comptant doit être validé par la boutique avant que son solde ne soit débité.'
            : 'Achat enregistré avec succès.';

        return redirect()->route('admin.achats.index')->with('success', $message);
    }

    /**
     * Notifie tous les boutiquiers de la boutique qu'un débit attend leur
     * validation. On ne filtre pas par shift : la demande ne doit pas être
     * manquée par le boutiquier présent.
     */
    private function notifyBoutiquiersForValidation(\App\Models\Boutique $boutique, float $montant, string $objet): void
    {
        $recipients = \App\Models\User::where('role', 'boutiquier')
            ->where('boutique_id', $boutique->id)
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new \App\Notifications\PendingActionNotification(
            'Débit à valider',
            'Un paiement comptant de ' . money_format_app($montant) . ' pour ' . $objet . ' attend votre validation avant débit de votre solde.',
            'Valider',
            route('boutiquier.dashboard'),
            [
                'type' => 'debit_validation',
                'montant' => $montant,
            ]
        ));
    }

    public function show(\App\Models\Achat $achat)
    {
        $achat->load(['fournisseur', 'boutique', 'lignes.produit', 'recharge.lignes']);

        $validation = \App\Models\DebitValidation::where('source_type', 'achat')
            ->where('source_id', $achat->id)
            ->latest()
            ->first();

        return view('admin.achats.show', compact('achat', 'validation'));
    }

    public function edit(\App\Models\Achat $achat)
    {
        $achat->load(['fournisseur', 'boutique', 'lignes.produit']);
        $fournisseurs = \App\Models\Fournisseur::all();
        $produits = \App\Models\Produit::all();
        $magasins = \App\Models\Boutique::where('type', 'magasin')->get();
        $allBoutiques = \App\Models\Boutique::orderBy('nom')->get();

        return view('admin.achats.edit', compact('achat', 'fournisseurs', 'produits', 'magasins', 'allBoutiques'));
    }

    public function update(Request $request, \App\Models\Achat $achat)
    {
        $rules = [
            'fournisseur_id' => 'required|exists:fournisseurs,id',
            'boutique_id' => [
                'required',
                'exists:boutiques,id',
                function ($attribute, $value, $fail) {
                    $boutique = \App\Models\Boutique::find($value);
                    if (! $boutique || $boutique->type !== 'magasin') {
                        $fail('La destination doit être un magasin.');
                    }
                },
            ],
            'statut' => 'required|in:paye,dette',
            'lignes' => 'required|array|min:1',
            'lignes.*.produit_id' => 'required|exists:produits,id',
            'lignes.*.quantite' => 'required|integer|min:1',
            'lignes.*.prix_unitaire' => 'required|numeric|min:0',
            'lignes.*.prix_vente' => 'nullable|numeric|min:0',
            'lignes.*.prix_vente_grossiste' => 'nullable|numeric|min:0',
        ];

        if ($request->input('statut') === 'paye') {
            $rules['debit_boutique_id'] = 'required|exists:boutiques,id';
        } else {
            $rules['debit_boutique_id'] = 'nullable|exists:boutiques,id';
        }

        $request->validate($rules);

        DB::transaction(function () use ($request, $achat) {
            $montant_total = 0;
            foreach ($request->lignes as $ligne) {
                $montant_total += $ligne['quantite'] * $ligne['prix_unitaire'];
            }

            $achat->update([
                'fournisseur_id' => $request->fournisseur_id,
                'boutique_id' => $request->boutique_id,
                'debit_boutique_id' => $request->filled('debit_boutique_id') ? $request->debit_boutique_id : null,
                'statut' => $request->statut,
                'montant_total' => $montant_total,
            ]);

            $achat->lignes()->delete();

            foreach ($request->lignes as $ligne) {
                \App\Models\AchatLigne::create([
                    'achat_id' => $achat->id,
                    'produit_id' => $ligne['produit_id'],
                    'quantite' => $ligne['quantite'],
                    'prix_unitaire' => $ligne['prix_unitaire'],
                    'prix_vente' => isset($ligne['prix_vente']) && $ligne['prix_vente'] !== '' ? $ligne['prix_vente'] : null,
                    'prix_vente_grossiste' => isset($ligne['prix_vente_grossiste']) && $ligne['prix_vente_grossiste'] !== '' ? $ligne['prix_vente_grossiste'] : null,
                ]);

                try {
                    $produit = \App\Models\Produit::find($ligne['produit_id']);
                    if ($produit) {
                        $produit->prix_achat = $ligne['prix_unitaire'];
                        if (isset($ligne['prix_vente']) && $ligne['prix_vente'] !== null && $ligne['prix_vente'] !== '') {
                            $produit->prix_vente = $ligne['prix_vente'];
                        }
                        if (isset($ligne['prix_vente_grossiste']) && $ligne['prix_vente_grossiste'] !== null && $ligne['prix_vente_grossiste'] !== '') {
                            $produit->prix_vente_grossiste = $ligne['prix_vente_grossiste'];
                        }
                        $produit->save();
                    }
                } catch (\Throwable $e) {
                    Log::warning('Impossible de mettre à jour les prix du produit lors de la modification de l\'achat: ' . $e->getMessage());
                }
            }

            if ($achat->recharge) {
                $achat->recharge()->delete();
            }

            $destination = \App\Models\Boutique::find($request->boutique_id);
            if ($destination && $destination->type === 'magasin') {
                $recharge = \App\Models\Recharge::create([
                    'source_id' => null,
                    'destination_id' => $request->boutique_id,
                    'user_id' => Auth::id(),
                    'montant' => $montant_total,
                    'statut' => 'en_attente',
                    'fournisseur_id' => $request->fournisseur_id,
                    'achat_id' => $achat->id,
                ]);

                foreach ($request->lignes as $ligne) {
                    \App\Models\RechargeLigne::create([
                        'recharge_id' => $recharge->id,
                        'produit_id' => $ligne['produit_id'],
                        'quantite_envoyee' => $ligne['quantite'],
                        'quantite_recue' => 0,
                        'quantite_manquante' => $ligne['quantite'],
                    ]);
                }
            }
        });

        return redirect()->route('admin.achats.index')->with('success', 'Achat modifié avec succès.');
    }

    public function destroy(\App\Models\Achat $achat)
    {
        DB::transaction(function () use ($achat) {
            $achat->lignes()->delete();
            if ($achat->recharge) {
                $achat->recharge()->delete();
            }
            $achat->delete();
        });

        return redirect()->route('admin.achats.index')->with('success', 'Achat supprimé avec succès.');
    }
}
