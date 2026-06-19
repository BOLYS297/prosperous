<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use App\Models\HoraireConnexion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = \App\Models\User::where('role', '!=', 'super_admin')->with('horaires')->get();
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $boutiques = Boutique::all();
        $magasiniers = HoraireConnexion::forRole('magasinier')->where('actif', true)->get();
        $boutiquiers = HoraireConnexion::forRole('boutiquier')->where('actif', true)->get();

        return view('admin.users.create', compact('boutiques', 'magasiniers', 'boutiquiers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom_utilisateur' => 'required|string|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => ['required', Rule::in(['magasinier', 'boutiquier'])],
            'monthly_salary' => 'required|integer|min:0',
            'horaires' => ['required', 'array', 'min:1'],
            'horaires.*' => [
                'required',
                Rule::exists('horaire_connexions', 'id')->where(function ($query) use ($request) {
                    $query->where('role', $request->input('role'));
                }),
            ],
            'boutique_id' => 'nullable|exists:boutiques,id'
        ]);

        $user = User::create([
            'nom_utilisateur' => $request->nom_utilisateur,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role,
            'monthly_salary' => $request->monthly_salary,
            'boutique_id' => $request->boutique_id
        ]);

        $user->horaires()->sync($request->horaires);

        return redirect()->route('admin.users.index')->with('success', 'Employé ajouté avec succès.');
    }

    public function edit(\App\Models\User $user)
    {
        $boutiques = Boutique::all();
        $magasiniers = HoraireConnexion::forRole('magasinier')->where('actif', true)->get();
        $boutiquiers = HoraireConnexion::forRole('boutiquier')->where('actif', true)->get();
        $selectedHoraires = $user->horaires->pluck('id')->toArray();

        return view('admin.users.edit', compact('user', 'boutiques', 'magasiniers', 'boutiquiers', 'selectedHoraires'));
    }

    public function update(Request $request, \App\Models\User $user)
    {
        $request->validate([
            'nom_utilisateur' => ['required', 'string', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|min:6',
            'role' => ['required', Rule::in(['magasinier', 'boutiquier'])],
            'monthly_salary' => 'required|integer|min:0',
            'horaires' => ['required', 'array', 'min:1'],
            'horaires.*' => [
                'required',
                Rule::exists('horaire_connexions', 'id')->where(function ($query) use ($request) {
                    $query->where('role', $request->input('role'));
                }),
            ],
            'boutique_id' => 'nullable|exists:boutiques,id'
        ]);

        $data = [
            'nom_utilisateur' => $request->nom_utilisateur,
            'email' => $request->email,
            'role' => $request->role,
            'monthly_salary' => $request->monthly_salary,
            'boutique_id' => $request->boutique_id
        ];

        if ($request->password) {
            $data['password'] = bcrypt($request->password);
        }

        $user->update($data);
        $user->horaires()->sync($request->horaires);

        return redirect()->route('admin.users.index')->with('success', 'Employé modifié avec succès.');
    }

    public function destroy(\App\Models\User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'Employé supprimé.');
    }

    public function authorizeDevice(\App\Models\User $user)
    {
        $token = \Illuminate\Support\Str::random(60);
        $user->update(['device_token' => $token]);

        // On crée un cookie qui n'expire pratiquement jamais (5 ans)
        $cookie = cookie('device_token', $token, 2628000);

        return redirect()->route('admin.users.index')->with('success', 'Appareil autorisé pour cet employé.')->withCookie($cookie);
    }

    public function resetDevice(\App\Models\User $user)
    {
        $user->update(['device_token' => null]);
        return redirect()->route('admin.users.index')->with('success', 'L\'appareil de cet employé a été réinitialisé.');
    }
}
