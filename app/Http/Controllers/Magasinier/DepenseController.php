<?php

namespace App\Http\Controllers\Magasinier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;

class DepenseController extends Controller
{
    public function create()
    {
        $produits = \App\Models\Produit::orderBy('nom')->get();
        return view('magasinier.depenses.create', compact('produits'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'produit_id' => 'required|exists:produits,id',
            'quantite' => 'required|integer|min:1',
            'raison' => 'required|string',
            'photo_justificatif' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'photo_webcam_data' => 'nullable|string',
        ]);

        $boutiqueId = Auth::user()->boutique_id;
        $photoPath = null;

        if ($request->hasFile('photo_justificatif')) {
            $photoPath = $request->file('photo_justificatif')->store('justificatifs', 'public');
        } elseif ($request->filled('photo_webcam_data')) {
            $photoPath = $this->storeWebcamPhoto($request->photo_webcam_data);
        }

        DB::transaction(function () use ($request, $boutiqueId, $photoPath) {
            $perte = \App\Models\Perte::create([
                'boutique_id' => $boutiqueId,
                'produit_id' => $request->produit_id,
                'user_id' => Auth::id(),
                'quantite' => $request->quantite,
                'raison' => $request->raison,
                'statut' => 'pending',
                'photo_justificatif' => $photoPath,
            ]);

            // Notifier les admins
            $admins = \App\Models\User::whereIn('role', ['admin', 'super_admin'])->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new \App\Notifications\AdminValidationNotification(
                    'Signalement de perte',
                    "Une perte de {$perte->quantite}x {$perte->produit->nom} a été signalée par un magasinier. Raison : {$perte->raison}",
                    'Voir le signalement',
                    route('admin.rapports.index')
                ));
            }
        });

        return redirect()->route('magasinier.dashboard')->with('success', 'Perte soumise pour validation admin. Elle sera enregistrée définitivement après validation.');
    }

    private function storeWebcamPhoto(?string $photoData): ?string
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
