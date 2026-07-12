<?php

namespace App\Http\Controllers\Magasinier;

use App\Http\Controllers\Controller;
use App\Notifications\AdminValidationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class RechargeController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $boutique = $user->boutique;

        $recharges = \App\Models\Recharge::with(['fournisseur', 'lignes.produit'])
            ->where('destination_id', $boutique->id)
            ->whereIn('statut', ['en_attente', 'confirmee_par_magasinier'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('magasinier.recharges.index', compact('recharges'));
    }

    public function show(\App\Models\Recharge $recharge)
    {
        $recharge->load(['fournisseur', 'lignes.produit', 'lignes', 'justificatifs']);
        return view('magasinier.recharges.show', compact('recharge'));
    }

    public function confirmer(Request $request, \App\Models\Recharge $recharge)
    {
        $request->validate([
            'justificatifs.*' => 'image|max:5120',
            'captured_images.*' => 'nullable|string',
            'lignes.*.quantite_recue' => 'nullable|integer|min:0',
        ]);

        $alreadyProcessed = false;

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $recharge, &$alreadyProcessed) {
            // Verrou + vérification atomique : une recharge ne peut être confirmée
            // qu'une seule fois, même en cas de double-clic ou de rejeu hors-ligne.
            $fresh = \App\Models\Recharge::where('id', $recharge->id)->lockForUpdate()->first();
            if (! $fresh || $fresh->statut !== 'en_attente') {
                $alreadyProcessed = true;
                return;
            }

            $this->saveJustificatifs($request, $recharge);

            if ($request->has('lignes')) {
                foreach ($request->input('lignes') as $idx => $data) {
                    $ligne = \App\Models\RechargeLigne::find($data['id'] ?? null);
                    if (! $ligne) {
                        continue;
                    }

                    $oldRecue = $ligne->quantite_recue;
                    $newRecue = isset($data['quantite_recue']) ? intval($data['quantite_recue']) : $oldRecue;
                    $newRecue = max(0, $newRecue);

                    $ligne->update([
                        'quantite_recue' => $newRecue,
                        'quantite_manquante' => max(0, $ligne->quantite_envoyee - $newRecue),
                    ]);
                }
            }

            // Stock update will be done by admin only
            $fresh->update(['statut' => 'confirmee_par_magasinier']);
        });

        if ($alreadyProcessed) {
            return redirect()->route('magasinier.recharges.index')->with('error', 'Cette recharge a déjà été traitée.');
        }

        $adminUsers = \App\Models\User::whereIn('role', ['admin', 'super_admin'])->get();
        if ($adminUsers->isNotEmpty()) {
            Notification::send($adminUsers, new AdminValidationNotification(
                'Recharge confirmée',
                "La recharge #{$recharge->id} a été confirmée par le magasinier. Merci de la valider.",
                'Voir la recharge',
                route('admin.recharges.validation.show', $recharge)
            ));
        }

        return redirect()->route('magasinier.recharges.index')->with('success', 'Recharge confirmée. En attente de validation administrateur.');
    }

    public function probleme(Request $request, \App\Models\Recharge $recharge)
    {
        $request->validate([
            'justificatifs.*' => 'image|max:5120',
            'captured_images.*' => 'nullable|string',
            'message' => 'required|string',
            'lignes' => 'required|array',
            'lignes.*.id' => 'required|integer|exists:recharge_lignes,id',
            'lignes.*.quantite_recue' => 'nullable|integer|min:0',
        ]);

        $hasAnomaly = false;
        foreach ($request->input('lignes', []) as $data) {
            $ligne = \App\Models\RechargeLigne::find($data['id'] ?? null);
            if (! $ligne || $ligne->recharge_id !== $recharge->id) {
                continue;
            }

            $quantiteRecue = isset($data['quantite_recue']) ? intval($data['quantite_recue']) : 0;
            if ($quantiteRecue < $ligne->quantite_envoyee) {
                $hasAnomaly = true;
                break;
            }
        }

        if (! $hasAnomaly) {
            return redirect()->back()->withInput()->withErrors(['message' => 'Vous devez signaler une anomalie sur au moins un produit.']);
        }

        $alreadyProcessed = false;

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $recharge, &$alreadyProcessed) {
            $fresh = \App\Models\Recharge::where('id', $recharge->id)->lockForUpdate()->first();
            if (! $fresh || $fresh->statut !== 'en_attente') {
                $alreadyProcessed = true;
                return;
            }

            $this->saveJustificatifs($request, $recharge);

            if ($request->has('lignes')) {
                foreach ($request->input('lignes') as $data) {
                    $ligne = \App\Models\RechargeLigne::find($data['id'] ?? null);
                    if (! $ligne || $ligne->recharge_id !== $recharge->id) {
                        continue;
                    }

                    $quantiteRecue = isset($data['quantite_recue']) ? intval($data['quantite_recue']) : 0;
                    $quantiteRecue = max(0, min($quantiteRecue, $ligne->quantite_envoyee));

                    $ligne->update([
                        'quantite_recue' => $quantiteRecue,
                        'quantite_manquante' => max(0, $ligne->quantite_envoyee - $quantiteRecue),
                    ]);
                }
            }

            $fresh->update([
                'statut' => 'anomalie',
                'message_probleme' => $request->input('message'),
            ]);
        });

        if ($alreadyProcessed) {
            return redirect()->route('magasinier.recharges.index')->with('error', 'Cette recharge a déjà été traitée.');
        }

        $adminUsers = \App\Models\User::whereIn('role', ['admin', 'super_admin'])->get();
        if ($adminUsers->isNotEmpty()) {
            Notification::send($adminUsers, new AdminValidationNotification(
                'Anomalie de recharge signalée',
                "Le magasinier a signalé une anomalie sur la recharge #{$recharge->id}. Merci de vérifier et de valider.",
                'Voir la recharge',
                route('admin.recharges.validation.show', $recharge)
            ));
        }

        return redirect()->route('magasinier.recharges.index')->with('success', 'Problème signalé. Un responsable sera notifié.');
    }

    protected function saveJustificatifs(Request $request, \App\Models\Recharge $recharge)
    {
        if ($request->hasFile('justificatifs')) {
            foreach ($request->file('justificatifs') as $file) {
                $path = $file->store('recharge_justificatifs', 'public');
                \App\Models\RechargeJustificatif::create([
                    'recharge_id' => $recharge->id,
                    'user_id' => auth()->id(),
                    'type' => 'upload',
                    'path' => $path,
                ]);
            }
        }

        if ($request->has('captured_images')) {
            foreach ($request->input('captured_images', []) as $index => $imageData) {
                if (!is_string($imageData) || !preg_match('/^data:image\/(png|jpe?g);base64,/', $imageData, $matches)) {
                    continue;
                }

                $image = preg_replace('/^data:image\/(png|jpe?g);base64,/', '', $imageData);
                $decoded = base64_decode($image);
                if ($decoded === false) {
                    continue;
                }

                $extension = strtolower($matches[1]) === 'jpeg' ? 'jpg' : strtolower($matches[1]);
                $filename = 'recharge_justificatifs/cam_' . uniqid() . '.' . $extension;
                Storage::disk('public')->put($filename, $decoded);

                \App\Models\RechargeJustificatif::create([
                    'recharge_id' => $recharge->id,
                    'user_id' => auth()->id(),
                    'type' => 'webcam',
                    'path' => $filename,
                ]);
            }
        }
    }
}
