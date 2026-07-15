<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use App\Models\DebitValidation;
use App\Models\Depense;
use App\Models\LogActivite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class DepenseController extends Controller
{
    /**
     * Historique de TOUTES les dépenses (administratives + déclarées par les
     * vendeurs). L'admin peut les modifier / supprimer quel que soit le statut.
     */
    public function index(Request $request)
    {
        $statut = $request->query('statut', '');
        $boutiqueId = $request->query('boutique_id', '');

        $depenses = Depense::with(['boutique', 'user'])
            ->when($statut !== '', fn ($q) => $q->where('statut', $statut))
            ->when($boutiqueId !== '', fn ($q) => $q->where('boutique_id', $boutiqueId))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $boutiques = Boutique::orderBy('nom')->get();

        $counts = [
            'pending' => Depense::where('statut', 'pending')->count(),
            'attente_boutique' => Depense::where('statut', 'attente_boutique')->count(),
            'approved' => Depense::where('statut', 'approved')->count(),
            'rejected' => Depense::where('statut', 'rejected')->count(),
        ];
        $totalApprouve = (float) Depense::where('statut', 'approved')->sum('montant');

        return view('admin.depenses.index', compact('depenses', 'boutiques', 'statut', 'boutiqueId', 'counts', 'totalApprouve'));
    }

    public function create()
    {
        $boutiques = Boutique::orderBy('nom')->get();
        return view('admin.depenses.create', compact('boutiques'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'boutique_id' => 'required|exists:boutiques,id',
            'montant' => 'required|numeric|min:1',
            'intitule' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $boutique = Boutique::findOrFail($validated['boutique_id']);
        $intitule = $validated['intitule'];
        $description = $validated['description'] ?? null;

        // On NE débite PAS immédiatement : la dépense reste "en attente de
        // validation par la boutique". Le solde ne sera débité qu'après
        // confirmation du boutiquier (qui peut aussi contester).
        DB::transaction(function () use ($validated, $boutique, $intitule, $description) {
            $depense = Depense::create([
                'boutique_id' => $boutique->id,
                'user_id' => Auth::id(),
                'intitule' => $intitule,
                'description' => $description,
                'montant' => $validated['montant'],
                'photo_justificatif' => null,
                'statut' => 'attente_boutique',
                'admin_id' => Auth::id(),
                'validated_at' => null,
            ]);

            DebitValidation::create([
                'boutique_id' => $boutique->id,
                'initiator_id' => Auth::id(),
                'amount' => $validated['montant'],
                'source_type' => 'depense',
                'source_id' => $depense->id,
                'motif' => $intitule,
                'status' => 'pending',
            ]);
        });

        $this->notifyBoutiqueOfAdminExpense($boutique, (float) $validated['montant']);

        return redirect()->route('admin.depenses.index')->with('success', 'Dépense enregistrée. La boutique doit valider le débit avant que son solde ne soit débité.');
    }

    public function edit(Depense $depense)
    {
        $depense->load(['boutique', 'user']);
        return view('admin.depenses.edit', compact('depense'));
    }

    /**
     * L'admin peut modifier une dépense à tout statut. Si elle est déjà validée
     * (solde débité), la variation de montant est répercutée sur le solde.
     */
    public function update(Request $request, Depense $depense)
    {
        $validated = $request->validate([
            'intitule' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'montant' => 'required|numeric|min:1',
        ]);

        $newMontant = (float) $validated['montant'];

        DB::transaction(function () use ($depense, $validated, $newMontant) {
            $fresh = Depense::where('id', $depense->id)->lockForUpdate()->first();
            if (! $fresh) {
                return;
            }

            $delta = $newMontant - (float) $fresh->montant;

            // Dépense déjà validée : le solde a été débité -> on répercute l'écart.
            if ($fresh->statut === 'approved' && abs($delta) > 0.001 && $fresh->boutique_id) {
                $boutique = Boutique::where('id', $fresh->boutique_id)->lockForUpdate()->first();
                if ($boutique) {
                    if ($delta > 0) {
                        $boutique->decrement('solde', $delta);
                    } else {
                        $boutique->increment('solde', abs($delta));
                    }
                }
            }

            // Dépense encore en attente de validation : garder la demande synchrone.
            if ($fresh->statut === 'attente_boutique') {
                DebitValidation::where('source_type', 'depense')
                    ->where('source_id', $fresh->id)
                    ->where('status', 'pending')
                    ->update([
                        'amount' => $newMontant,
                        'motif' => $validated['intitule'],
                    ]);
            }

            $fresh->update([
                'intitule' => $validated['intitule'],
                'description' => $validated['description'] ?? null,
                'montant' => $newMontant,
            ]);
        });

        LogActivite::create([
            'user_id' => Auth::id(),
            'action' => 'admin.depenses.update',
            'description' => "Dépense #{$depense->id} modifiée : " . money_format_app($newMontant) . " ({$validated['intitule']}).",
        ]);

        return redirect()->route('admin.depenses.index')->with('success', 'Dépense mise à jour.');
    }

    /**
     * Suppression par l'admin. Si la dépense était validée, le solde est
     * re-crédité ; si elle attendait la validation de la boutique, la demande
     * de validation est annulée.
     */
    public function destroy(Depense $depense)
    {
        $montant = (float) $depense->montant;
        $intitule = $depense->intitule;

        DB::transaction(function () use ($depense) {
            $fresh = Depense::where('id', $depense->id)->lockForUpdate()->first();
            if (! $fresh) {
                return;
            }

            // Dépense validée : le solde avait été débité -> on le rend.
            if ($fresh->statut === 'approved' && $fresh->boutique_id) {
                $boutique = Boutique::where('id', $fresh->boutique_id)->lockForUpdate()->first();
                if ($boutique) {
                    $boutique->increment('solde', $fresh->montant);
                }
            }

            // Demande de validation encore en attente -> on l'annule.
            DebitValidation::where('source_type', 'depense')
                ->where('source_id', $fresh->id)
                ->where('status', 'pending')
                ->delete();

            $fresh->delete();
        });

        LogActivite::create([
            'user_id' => Auth::id(),
            'action' => 'admin.depenses.destroy',
            'description' => "Dépense supprimée : {$intitule} (" . money_format_app($montant) . ").",
        ]);

        return redirect()->route('admin.depenses.index')->with('success', 'Dépense supprimée. Le solde a été ajusté si nécessaire.');
    }

    protected function notifyBoutiqueOfAdminExpense(Boutique $boutique, float $montant)
    {
        $users = \App\Models\User::where('role', 'boutiquier')
            ->where('boutique_id', $boutique->id)
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new \App\Notifications\PendingActionNotification(
            'Débit à valider',
            'Une dépense administrative de ' . money_format_app($montant) . ' attend votre validation avant débit de votre solde.',
            'Valider',
            route('boutiquier.dashboard'),
            [
                'type' => 'debit_validation',
                'montant' => $montant,
            ]
        ));
    }
}
