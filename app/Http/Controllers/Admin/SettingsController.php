<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use App\Models\LogActivite;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function edit()
    {
        $settings = Setting::allValues();
        $boutiques = Boutique::orderBy('type')->orderBy('nom')->get();

        return view('admin.parametres.index', compact('settings', 'boutiques'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'company_immatriculation' => 'nullable|string|max:255',
            'currency' => 'required|string|max:10',
            'company_address' => 'nullable|string|max:500',
            'company_phone' => 'nullable|string|max:50',
            'ticket_footer' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'banner' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:4096',
        ]);

        // Champs texte
        foreach (['company_name', 'company_immatriculation', 'currency', 'company_address', 'company_phone', 'ticket_footer'] as $key) {
            Setting::set($key, $validated[$key] ?? '');
        }

        // Images : on remplace et on supprime l'ancienne si présente
        if ($request->hasFile('logo')) {
            $this->replaceImage('logo_path', $request->file('logo'));
        }

        if ($request->hasFile('banner')) {
            $this->replaceImage('banner_path', $request->file('banner'));
        }

        LogActivite::create([
            'user_id' => Auth::id(),
            'action' => 'admin.parametres.update',
            'description' => "Mise à jour des paramètres de l'entreprise.",
        ]);

        return back()->with('success', 'Paramètres de l\'entreprise enregistrés avec succès.');
    }

    protected function replaceImage(string $key, $file): void
    {
        $old = Setting::get($key);
        if ($old && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }

        $path = $file->store('settings', 'public');
        Setting::set($key, $path);
    }
}
