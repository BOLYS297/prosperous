<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use App\Models\HoraireConnexion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
        // Le mécanicien ne se connecte pas à l'application : ni horaires, ni
        // salaire de base. Il est rémunéré par une commission sur le bénéfice.
        $isMecanicien = $request->input('role') === 'mecanicien';

        // Un mécanicien n'a ni email ni mot de passe : on ignore toute valeur
        // soumise (le champ caché du formulaire peut être rempli par l'autofill
        // du navigateur, ce qui faisait échouer le 2e mécanicien sur « email
        // déjà pris »). Un email technique unique est généré à la création.
        if ($isMecanicien) {
            $request->merge(['email' => null, 'password' => null]);
        }

        $request->validate([
            'nom_utilisateur' => 'required|string|unique:users',
            'email' => [$isMecanicien ? 'nullable' : 'required', 'email', 'unique:users'],
            'password' => [$isMecanicien ? 'nullable' : 'required', 'min:6'],
            'role' => ['required', Rule::in(['magasinier', 'boutiquier', 'mecanicien'])],
            'monthly_salary' => [$isMecanicien ? 'nullable' : 'required', 'integer', 'min:0'],
            'commission_percent' => [$isMecanicien ? 'required' : 'nullable', 'numeric', 'min:0', 'max:100'],
            'horaires' => [$isMecanicien ? 'nullable' : 'required', 'array'],
            'horaires.*' => [
                Rule::exists('horaire_connexions', 'id')->where(function ($query) use ($request) {
                    $query->where('role', $request->input('role'));
                }),
            ],
            'boutique_id' => [$isMecanicien ? 'required' : 'nullable', 'exists:boutiques,id'],
        ]);

        $user = User::create([
            'nom_utilisateur' => $request->nom_utilisateur,
            'email' => $request->email ?: $this->technicalEmail($request->nom_utilisateur),
            'password' => bcrypt($request->password ?: Str::random(32)),
            'role' => $request->role,
            'monthly_salary' => $isMecanicien ? 0 : $request->monthly_salary,
            'commission_percent' => $isMecanicien ? $request->commission_percent : null,
            'boutique_id' => $request->boutique_id,
        ]);

        if (! $isMecanicien) {
            $user->horaires()->sync($request->input('horaires', []));
        }

        return redirect()->route('admin.users.index')->with('success', $isMecanicien ? 'Mécanicien ajouté avec succès.' : 'Employé ajouté avec succès.');
    }

    /**
     * Adresse technique pour un profil qui ne se connecte pas (mécanicien) :
     * la colonne email est NOT NULL et unique.
     */
    protected function technicalEmail(string $nom): string
    {
        $slug = Str::slug($nom) ?: 'mecanicien';

        // Garantit l'unicité (la colonne email est unique) : on régénère tant que
        // l'adresse existe déjà, plutôt que de risquer une collision.
        do {
            $email = $slug . '.' . Str::lower(Str::random(6)) . '@mecanicien.local';
        } while (User::where('email', $email)->exists());

        return $email;
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
        $isMecanicien = $request->input('role') === 'mecanicien';

        // Cf. store() : un mécanicien n'a ni email ni mot de passe saisis, on
        // ignore toute valeur soumise (autofill) pour éviter une collision unique.
        if ($isMecanicien) {
            $request->merge(['email' => null, 'password' => null]);
        }

        $request->validate([
            'nom_utilisateur' => ['required', 'string', Rule::unique('users')->ignore($user->id)],
            'email' => [$isMecanicien ? 'nullable' : 'required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|min:6',
            'role' => ['required', Rule::in(['magasinier', 'boutiquier', 'mecanicien'])],
            'monthly_salary' => [$isMecanicien ? 'nullable' : 'required', 'integer', 'min:0'],
            'commission_percent' => [$isMecanicien ? 'required' : 'nullable', 'numeric', 'min:0', 'max:100'],
            'horaires' => [$isMecanicien ? 'nullable' : 'required', 'array'],
            'horaires.*' => [
                Rule::exists('horaire_connexions', 'id')->where(function ($query) use ($request) {
                    $query->where('role', $request->input('role'));
                }),
            ],
            'boutique_id' => [$isMecanicien ? 'required' : 'nullable', 'exists:boutiques,id'],
        ]);

        $data = [
            'nom_utilisateur' => $request->nom_utilisateur,
            'email' => $request->email ?: ($user->email ?: $this->technicalEmail($request->nom_utilisateur)),
            'role' => $request->role,
            'monthly_salary' => $isMecanicien ? 0 : $request->monthly_salary,
            'commission_percent' => $isMecanicien ? $request->commission_percent : null,
            'boutique_id' => $request->boutique_id,
        ];

        if ($request->password) {
            $data['password'] = bcrypt($request->password);
        }

        $user->update($data);

        // Un mécanicien n'a pas d'horaires de connexion.
        $user->horaires()->sync($isMecanicien ? [] : $request->input('horaires', []));

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
