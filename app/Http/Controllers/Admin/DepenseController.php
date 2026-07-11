<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use App\Models\Depense;
use App\Notifications\BoutiqueExpenseNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class DepenseController extends Controller
{
    public function create()
    {
        $boutiques = Boutique::orderBy('nom')->get();
        return view('admin.depenses.create', compact('boutiques'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'boutique_id' => 'required|exists:boutiques,id',
            'montant' => 'required|numeric|min:0',
        ]);

        $boutique = Boutique::findOrFail($request->input('boutique_id'));
        $defaultIntitule = 'Dépense administrative';
        $defaultDescription = 'Dépense administrative enregistrée par l’administrateur.';

        // On NE débite PAS immédiatement : la dépense reste "en attente de
        // validation par la boutique". Le solde ne sera débité qu'après
        // confirmation du boutiquier (qui peut aussi contester).
        DB::transaction(function () use ($request, $boutique, $defaultIntitule, $defaultDescription) {
            $depense = Depense::create([
                'boutique_id' => $boutique->id,
                'user_id' => Auth::id(),
                'intitule' => $defaultIntitule,
                'description' => $defaultDescription,
                'montant' => $request->input('montant'),
                'photo_justificatif' => null,
                'statut' => 'attente_boutique',
                'admin_id' => Auth::id(),
                'validated_at' => null,
            ]);

            \App\Models\DebitValidation::create([
                'boutique_id' => $boutique->id,
                'initiator_id' => Auth::id(),
                'amount' => $request->input('montant'),
                'source_type' => 'depense',
                'source_id' => $depense->id,
                'motif' => $defaultIntitule,
                'status' => 'pending',
            ]);
        });

        $this->notifyBoutiqueOfAdminExpense(
            $boutique,
            $request->input('montant')
        );

        return redirect()->route('admin.dashboard')->with('success', 'Dépense enregistrée. La boutique doit valider le débit avant que son solde ne soit débité.');
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
