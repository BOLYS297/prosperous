<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;


Route::get('/', function () {
    return view('auth.login');
});

Route::get('/pwa-ping', function () {
    return response('', 204);
});

Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'check.horaire', 'check.device', 'log.activity'])->group(function () {

    Route::get('/offline-data', [\App\Http\Controllers\OfflineDataController::class, 'index'])->name('offline.data');

    // Redirection centrale selon le rôle
    Route::get('/dashboard', function () {
        $user = \Illuminate\Support\Facades\Auth::user();
        $role = trim(strtolower($user->role ?? ''));

        if (in_array($role, ['admin', 'super_admin'])) {
            return redirect()->route('admin.dashboard');
        }

        if ($role === 'magasinier') {
            return redirect()->route('magasinier.dashboard');
        }

        if ($role === 'boutiquier') {
            return redirect()->route('boutiquier.dashboard');
        }

        return abort(403, 'Accès non autorisé.');
    })->name('dashboard');
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Admin\AdminController::class, 'index'])->name('dashboard');
        Route::get('payroll', [\App\Http\Controllers\Admin\PayrollController::class, 'index'])->name('payroll.index');
        Route::post('dashboard/deduction', [\App\Http\Controllers\Admin\AdminController::class, 'updateDeductionAmount'])->name('dashboard.deduction.update');
        Route::post('deductions/{deduction}/approve', [\App\Http\Controllers\Admin\AdminController::class, 'approveDeduction'])->name('deductions.approve');
        Route::post('deductions/{deduction}/reject', [\App\Http\Controllers\Admin\AdminController::class, 'rejectDeduction'])->name('deductions.reject');

        Route::post('users/{user}/authorize-device', [\App\Http\Controllers\Admin\UserController::class, 'authorizeDevice'])->name('users.authorize_device');
        Route::post('users/{user}/reset-device', [\App\Http\Controllers\Admin\UserController::class, 'resetDevice'])->name('users.reset_device');
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
        Route::resource('produits', \App\Http\Controllers\Admin\ProduitController::class);
        Route::resource('fournisseurs', \App\Http\Controllers\Admin\FournisseurController::class);
        Route::resource('grossistes', \App\Http\Controllers\Admin\GrossisteController::class);
        Route::get('grossistes/{grossiste}/pricing', [\App\Http\Controllers\Admin\GrossisteController::class, 'pricing'])->name('grossistes.pricing');
        Route::post('grossistes/{grossiste}/pricing', [\App\Http\Controllers\Admin\GrossisteController::class, 'updatePricing'])->name('grossistes.pricing.update');
        Route::resource('achats', \App\Http\Controllers\Admin\AchatController::class);
        Route::get('depenses/create', [\App\Http\Controllers\Admin\DepenseController::class, 'create'])->name('depenses.create');
        Route::post('depenses', [\App\Http\Controllers\Admin\DepenseController::class, 'store'])->name('depenses.store');

        // Boutiques / Trésorerie : approvisionnement du solde
        Route::get('boutiques', [\App\Http\Controllers\Admin\BoutiqueController::class, 'index'])->name('boutiques.index');
        Route::post('boutiques/{boutique}/crediter', [\App\Http\Controllers\Admin\BoutiqueController::class, 'crediter'])->name('boutiques.crediter');
        Route::put('boutiques/{boutique}', [\App\Http\Controllers\Admin\BoutiqueController::class, 'update'])->name('boutiques.update');

        // Paramètres de l'entreprise (nom, immatriculation, devise, logo, bannière...)
        Route::get('parametres', [\App\Http\Controllers\Admin\SettingsController::class, 'edit'])->name('parametres.edit');
        Route::put('parametres', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('parametres.update');

        // Tranches horaires de connexion
        Route::get('horaires', [\App\Http\Controllers\Admin\HoraireConnexionController::class, 'index'])->name('horaires.index');
        Route::post('horaires', [\App\Http\Controllers\Admin\HoraireConnexionController::class, 'store'])->name('horaires.store');
        Route::patch('horaires/{horaireConnexion}/toggle', [\App\Http\Controllers\Admin\HoraireConnexionController::class, 'toggle'])->name('horaires.toggle');
        Route::delete('horaires/{horaireConnexion}', [\App\Http\Controllers\Admin\HoraireConnexionController::class, 'destroy'])->name('horaires.destroy');

        // Profil Admin (modifier ses identifiants)
        Route::get('profile', [\App\Http\Controllers\Admin\ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [\App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('profile.update');

        // Validation des recharges
        Route::get('recharges/validation', [\App\Http\Controllers\Admin\RechargeValidationController::class, 'index'])->name('recharges.validation.index');
        Route::get('recharges/validation/{recharge}', [\App\Http\Controllers\Admin\RechargeValidationController::class, 'show'])->name('recharges.validation.show');
        Route::post('recharges/validation/{recharge}/valider', [\App\Http\Controllers\Admin\RechargeValidationController::class, 'valider'])->name('recharges.validation.valider');
        Route::post('recharges/validation/{recharge}/rejeter', [\App\Http\Controllers\Admin\RechargeValidationController::class, 'rejeter'])->name('recharges.validation.rejeter');

        // Rapports et Logs
        Route::get('logs', [\App\Http\Controllers\Admin\LogController::class, 'index'])->name('logs.index');
        Route::get('rapports', [\App\Http\Controllers\Admin\RapportController::class, 'index'])->name('rapports.index');
        Route::get('rapports/export/csv', [\App\Http\Controllers\Admin\RapportController::class, 'exportCsv'])->name('rapports.export.csv');
    });

    // Profil Magasinier
    Route::middleware(['role:magasinier'])->prefix('magasinier')->name('magasinier.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Magasinier\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/stocks', [\App\Http\Controllers\Magasinier\StockController::class, 'index'])->name('stocks.index');
        Route::get('/depenses/create', [\App\Http\Controllers\Magasinier\DepenseController::class, 'create'])->name('depenses.create');
        Route::post('/depenses', [\App\Http\Controllers\Magasinier\DepenseController::class, 'store'])->name('depenses.store');

        Route::get('/transferts', [\App\Http\Controllers\Magasinier\TransfertController::class, 'index'])->name('transferts.index');
        Route::post('/transferts/{id}/expedier', [\App\Http\Controllers\Magasinier\TransfertController::class, 'expedier'])->name('transferts.expedier');

        // Recharges (magasinier)
        Route::get('/recharges', [\App\Http\Controllers\Magasinier\RechargeController::class, 'index'])->name('recharges.index');
        Route::get('/recharges/{recharge}', [\App\Http\Controllers\Magasinier\RechargeController::class, 'show'])->name('recharges.show');
        Route::post('/recharges/{recharge}/confirmer', [\App\Http\Controllers\Magasinier\RechargeController::class, 'confirmer'])->name('recharges.confirmer');
        Route::post('/recharges/{recharge}/probleme', [\App\Http\Controllers\Magasinier\RechargeController::class, 'probleme'])->name('recharges.probleme');
    });

    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::post('rapports/depenses/{depense}/approve', [\App\Http\Controllers\Admin\RapportController::class, 'approveDepense'])->name('rapports.depenses.approve');
        Route::post('rapports/depenses/{depense}/reject', [\App\Http\Controllers\Admin\RapportController::class, 'rejectDepense'])->name('rapports.depenses.reject');
        Route::post('rapports/pertes/{perte}/approve', [\App\Http\Controllers\Admin\RapportController::class, 'approvePerte'])->name('rapports.pertes.approve');
        Route::post('rapports/pertes/{perte}/reject', [\App\Http\Controllers\Admin\RapportController::class, 'rejectPerte'])->name('rapports.pertes.reject');
    });

    // Profil Boutiquier
    Route::middleware(['role:boutiquier'])->prefix('boutiquier')->name('boutiquier.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Boutiquier\DashboardController::class, 'index'])->name('dashboard');
        Route::post('/notifications/{notification}/read', [\App\Http\Controllers\Boutiquier\DashboardController::class, 'markNotificationAsRead'])->name('notifications.mark_read');
        Route::post('/notifications/read-all', [\App\Http\Controllers\Boutiquier\DashboardController::class, 'markAllNotificationsAsRead'])->name('notifications.mark_all_read');
        Route::post('/ventes', [\App\Http\Controllers\Boutiquier\VenteController::class, 'store'])->name('ventes.store');
        Route::get('/historique', [\App\Http\Controllers\Boutiquier\VenteController::class, 'historique'])->name('ventes.historique');
        Route::get('/ventes/{vente}', [\App\Http\Controllers\Boutiquier\VenteController::class, 'show'])->name('ventes.show');
        Route::get('/ventes/{vente}/edit', [\App\Http\Controllers\Boutiquier\VenteController::class, 'edit'])->name('ventes.edit');
        Route::put('/ventes/{vente}', [\App\Http\Controllers\Boutiquier\VenteController::class, 'update'])->name('ventes.update');
        Route::delete('/ventes/{vente}', [\App\Http\Controllers\Boutiquier\VenteController::class, 'destroy'])->name('ventes.destroy');

        Route::get('/transferts', [\App\Http\Controllers\Boutiquier\DemandeTransfertController::class, 'index'])->name('transferts.index');
        Route::get('/transferts/create', [\App\Http\Controllers\Boutiquier\DemandeTransfertController::class, 'create'])->name('transferts.create');
        Route::post('/transferts', [\App\Http\Controllers\Boutiquier\DemandeTransfertController::class, 'store'])->name('transferts.store');
        Route::post('/transferts/{id}/confirmer', [\App\Http\Controllers\Boutiquier\DemandeTransfertController::class, 'confirmer'])->name('transferts.confirmer');
        Route::post('/transferts/{id}/probleme', [\App\Http\Controllers\Boutiquier\DemandeTransfertController::class, 'signalerProbleme'])->name('transferts.probleme');

        Route::get('/dettes', [\App\Http\Controllers\Boutiquier\DetteController::class, 'index'])->name('dettes.index');
        Route::post('/dettes/{achat}/payer', [\App\Http\Controllers\Boutiquier\DetteController::class, 'payer'])->name('dettes.payer');

        Route::get('/depenses/create', [\App\Http\Controllers\Boutiquier\DepenseController::class, 'create'])->name('depenses.create');
        Route::post('/depenses', [\App\Http\Controllers\Boutiquier\DepenseController::class, 'store'])->name('depenses.store');
    });
});
