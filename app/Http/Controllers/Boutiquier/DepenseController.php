<?php

namespace App\Http\Controllers\Boutiquier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AdminValidationNotification;

class DepenseController extends Controller
{
    public function create()
    {
        $produits = \App\Models\Produit::orderBy('nom')->get();
        return view('boutiquier.depenses.create', compact('produits'));
    }

    public function store(Request $request)
    {
        $type = $request->input('type'); // 'depense' or 'perte'

        if ($type === 'perte') {
            $request->validate([
                'produit_id' => 'required|exists:produits,id',
                'quantite' => 'required|integer|min:1',
                'raison' => 'required|string',
                'photo_justificatif' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
                'photo_webcam_data' => 'nullable|string',
            ]);

            $boutiqueId = Auth::user()->boutique_id;
            $photoPath = $this->storeWebcamPhoto($request->photo_webcam_data);

            DB::transaction(function () use ($request, $boutiqueId, $photoPath) {
                \App\Models\Perte::create([
                    'boutique_id' => $boutiqueId,
                    'produit_id' => $request->produit_id,
                    'user_id' => Auth::id(),
                    'quantite' => $request->quantite,
                    'raison' => $request->raison,
                    'statut' => 'pending',
                    'photo_justificatif' => $photoPath,
                ]);
            });

            $this->notifyAdminsForValidation(
                'Nouvelle perte à valider',
                "Une perte de {$request->quantite} unité(s) a été soumise par le boutiquier {$request->user()->nom_utilisateur}.",
                'Voir les pertes',
                route('admin.rapports.index')
            );

            return redirect()->route('boutiquier.dashboard')->with('success', 'Perte soumise pour validation admin. Elle sera enregistrée définitivement après validation.');
        } else {
            // Dépense normale : en attente de validation admin
            $request->validate([
                'intitule' => 'required|string|max:255',
                'description' => 'nullable|string',
                'montant' => 'required|numeric|min:0',
                'photo_justificatif' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            ]);

            $data = [
                'boutique_id' => \Illuminate\Support\Facades\Auth::user()->boutique_id,
                'user_id' => \Illuminate\Support\Facades\Auth::id(),
                'intitule' => $request->intitule,
                'description' => $request->description,
                'montant' => $request->montant,
                'statut' => 'pending',
            ];

            if ($request->hasFile('photo_justificatif')) {
                $data['photo_justificatif'] = $request->file('photo_justificatif')->store('justificatifs', 'public');
            } elseif ($request->filled('photo_webcam_data')) {
                $data['photo_justificatif'] = $this->storeWebcamPhoto($request->photo_webcam_data);
            }

            DB::transaction(function () use ($data) {
                \App\Models\Depense::create($data);
            });

            $this->notifyAdminsForValidation(
                'Nouvelle dépense à valider',
                "Une dépense de " . money_format_app($request->montant) . " a été soumise par le boutiquier {$request->user()->nom_utilisateur}.",
                'Voir les dépenses',
                route('admin.rapports.index')
            );

            return redirect()->route('boutiquier.dashboard')->with('success', 'Dépense soumise pour validation admin. Elle sera enregistrée définitivement après validation.');
        }
    }

    /** Historique des dépenses déclarées par ce vendeur. */
    public function index()
    {
        $depenses = \App\Models\Depense::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('boutiquier.depenses.index', compact('depenses'));
    }

    public function edit(\App\Models\Depense $depense)
    {
        if (! $this->peutModifier($depense)) {
            return redirect()->route('boutiquier.depenses.index')->with('error', $this->messageVerrou($depense));
        }

        return view('boutiquier.depenses.edit', compact('depense'));
    }

    public function update(Request $request, \App\Models\Depense $depense)
    {
        if (! $this->peutModifier($depense)) {
            return redirect()->route('boutiquier.depenses.index')->with('error', $this->messageVerrou($depense));
        }

        $validated = $request->validate([
            'intitule' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'montant' => 'required|numeric|min:1',
        ]);

        // Statut 'pending' : la dépense n'a pas encore impacté le solde.
        $depense->update([
            'intitule' => $validated['intitule'],
            'description' => $validated['description'] ?? null,
            'montant' => $validated['montant'],
        ]);

        return redirect()->route('boutiquier.depenses.index')->with('success', 'Dépense mise à jour.');
    }

    public function destroy(\App\Models\Depense $depense)
    {
        if (! $this->peutModifier($depense)) {
            return redirect()->route('boutiquier.depenses.index')->with('error', $this->messageVerrou($depense));
        }

        $depense->delete();

        return redirect()->route('boutiquier.depenses.index')->with('success', 'Dépense supprimée.');
    }

    /**
     * Modifiable/supprimable uniquement tant que l'admin ne l'a ni validée ni
     * rejetée (statut 'pending') — et seulement par son auteur.
     */
    protected function peutModifier(\App\Models\Depense $depense): bool
    {
        return (int) $depense->user_id === (int) Auth::id() && $depense->statut === 'pending';
    }

    protected function messageVerrou(\App\Models\Depense $depense): string
    {
        if ((int) $depense->user_id !== (int) Auth::id()) {
            return 'Cette dépense ne vous appartient pas.';
        }

        return "Cette dépense a déjà été traitée par l'administrateur : seul l'admin peut désormais la modifier ou la supprimer.";
    }

    protected function notifyAdminsForValidation(string $title, string $message, string $actionLabel, string $actionUrl)
    {
        $admins = \App\Models\User::whereIn('role', ['admin', 'super_admin'])->get();
        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new AdminValidationNotification(
            $title,
            $message,
            $actionLabel,
            $actionUrl
        ));
    }

    private function storeWebcamPhoto(?string $photoData)
    {
        if (empty($photoData) || !str_starts_with($photoData, 'data:image/')) {
            return null;
        }

        [$meta, $data] = explode(',', $photoData, 2);
        $extension = 'jpg';
        if (str_contains($meta, 'image/png')) {
            $extension = 'png';
        } elseif (str_contains($meta, 'image/webp')) {
            $extension = 'webp';
        }

        $contents = base64_decode($data);
        $filename = 'justificatifs/webcam_' . uniqid() . '.' . $extension;
        Storage::disk('public')->put($filename, $contents);

        return $filename;
    }
}
