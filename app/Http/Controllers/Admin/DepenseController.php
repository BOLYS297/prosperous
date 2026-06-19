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

        DB::transaction(function () use ($request, $boutique, $defaultIntitule, $defaultDescription) {
            Depense::create([
                'boutique_id' => $boutique->id,
                'user_id' => Auth::id(),
                'intitule' => $defaultIntitule,
                'description' => $defaultDescription,
                'montant' => $request->input('montant'),
                'photo_justificatif' => null,
                'statut' => 'approved',
                'admin_id' => Auth::id(),
                'validated_at' => now(),
            ]);

            $boutique->decrement('solde', $request->input('montant'));
        });

        $this->notifyBoutiqueOfAdminExpense(
            $boutique,
            $request->input('montant')
        );

        return redirect()->route('admin.dashboard')->with('success', 'Dépense personnelle enregistrée et la boutique a été notifiée.');
    }

    protected function notifyBoutiqueOfAdminExpense(Boutique $boutique, float $montant)
    {
        $users = \App\Models\User::where('role', 'boutiquier')
            ->where('boutique_id', $boutique->id)
            ->whereNotNull('email')
            ->where('email', '<>', '')
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        $reason = 'Dépense administrative imputée à la boutique.';
        $actionUrl = route('boutiquier.depenses.create', [
            'type' => 'perte',
            'raison' => $reason,
        ]);

        Notification::send($users, new BoutiqueExpenseNotification(
            'Nouvelle dépense administrative',
            "Une dépense de " . number_format($montant, 0, ',', ' ') . " FCFA a été imputée à votre boutique.",
            'Signaler une perte',
            $actionUrl
        ));
    }
}
