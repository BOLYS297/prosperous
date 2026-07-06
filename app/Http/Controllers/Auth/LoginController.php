<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AdminValidationNotification;
use App\Models\Deduction;
use App\Models\HoraireConnexion;
use App\Models\LogActivite;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'nom_utilisateur' => ['required'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Vérification horaire immédiate : si l'utilisateur n'est pas autorisé maintenant,
            // déconnecter proprement et retourner une erreur.
            if (!HoraireConnexion::canUserConnect(Auth::user())) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'nom_utilisateur' => 'Vous ne pouvez vous connecter qu\'aux horaires autorisés.'
                ])->onlyInput('nom_utilisateur');
            }

            $user = Auth::user();
            $interval = HoraireConnexion::getCurrentIntervalForUser($user);
            $now = Carbon::now();

            // La déduction de retard ne concerne que la PREMIÈRE connexion du jour.
            // Une reconnexion (session expirée, appareil non reconnu, etc.) NE doit
            // PAS être traitée comme un nouveau retard.
            $dejaConnecteAujourdhui = LogActivite::where('user_id', $user->id)
                ->where('action', 'connexion')
                ->whereDate('created_at', $now->toDateString())
                ->exists();

            if ($interval && ! $dejaConnecteAujourdhui) {
                $scheduledStart = Carbon::createFromFormat('H:i:s', $interval->heure_debut, $now->getTimezone())
                    ->setDate($now->year, $now->month, $now->day);

                $minutesLate = (int) max(0, $scheduledStart->diffInMinutes($now));
                $hourlyAmount = \App\Models\DeductionSetting::getHourlyAmount();

                if ($minutesLate > 0 && $hourlyAmount > 0) {
                    // Calcul précis à la minute : déduction au prorata du nombre de minutes
                    $deductionAmount = (int) round(($minutesLate / 60.0) * $hourlyAmount);

                    // Pour l'affichage : séparer heures complètes et minutes restantes
                    $hoursLate = intdiv($minutesLate, 60);
                    $minutesRemaining = $minutesLate % 60;

                    // Sécurité supplémentaire : une seule déduction de connexion par
                    // jour, quel que soit son statut (pending / approved / rejected).
                    if (!Deduction::where('user_id', $user->id)
                        ->whereDate('actual_login_at', $now->toDateString())
                        ->where('event_type', 'login')
                        ->exists()) {
                        $deduction = Deduction::create([
                            'user_id' => $user->id,
                            'amount' => $deductionAmount,
                            'minutes_late' => $minutesLate,
                            'scheduled_start' => $interval->heure_debut,
                            'event_type' => 'login',
                            'actual_login_at' => $now,
                            'status' => 'pending',
                            'description' => "Retard de {$hoursLate} heure(s) et {$minutesRemaining} minute(s) pour connexion tardive",
                        ]);

                        $adminUsers = \App\Models\User::whereIn('role', ['admin', 'super_admin'])->get();
                        if ($adminUsers->isNotEmpty()) {
                            try {
                                Notification::send($adminUsers, new AdminValidationNotification(
                                    'Validation de déduction salariale requise',
                                    "Une nouvelle déduction salariale de {$deduction->amount} FCFA est en attente de validation pour l'utilisateur {$user->nom_utilisateur}.",
                                    'Voir les déductions',
                                    route('admin.dashboard')
                                ));
                            } catch (\Throwable $e) {
                                // Ne jamais bloquer la connexion à cause d'une notification
                                report($e);
                            }
                        }
                    }
                }
            }

            // Enregistrer la connexion dans les logs
            LogActivite::create([
                'user_id' => $user->id,
                'action' => 'connexion',
                'description' => 'Connexion réussie le ' . $now->format('d/m/Y à H:i:s'),
            ]);

            return redirect()->intended('dashboard');
        }

        return back()->withErrors([
            'nom_utilisateur' => 'Les identifiants fournis ne correspondent pas à nos enregistrements.',
        ])->onlyInput('nom_utilisateur');
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $now = Carbon::now();

            if (in_array($user->role, ['magasinier', 'boutiquier'], true)) {
                $this->createEarlyLogoutDeduction($user, $now);
            }

            LogActivite::create([
                'user_id' => $user->id,
                'action' => 'deconnexion',
                'description' => 'Déconnexion le ' . $now->format('d/m/Y à H:i:s'),
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    protected function createEarlyLogoutDeduction($user, Carbon $logoutTime): void
    {
        $interval = HoraireConnexion::getCurrentIntervalForUser($user);

        if (! $interval) {
            return;
        }

        $scheduledEnd = Carbon::createFromFormat('H:i:s', $interval->heure_fin, $logoutTime->getTimezone())
            ->setDate($logoutTime->year, $logoutTime->month, $logoutTime->day);

        if (! $logoutTime->lt($scheduledEnd)) {
            return;
        }

        $minutesEarly = (int) $logoutTime->diffInMinutes($scheduledEnd);
        $hourlyAmount = \App\Models\DeductionSetting::getHourlyAmount();

        if ($minutesEarly <= 0 || $hourlyAmount <= 0) {
            return;
        }

        // Calcul précis à la minute : déduction au prorata du nombre de minutes
        $deductionAmount = (int) round(($minutesEarly / 60.0) * $hourlyAmount);

        // Pour l'affichage : séparer heures complètes et minutes restantes
        $hoursEarly = intdiv($minutesEarly, 60);
        $minutesRemaining = $minutesEarly % 60;

        Deduction::create([
            'user_id' => $user->id,
            'amount' => $deductionAmount,
            'minutes_late' => $minutesEarly,
            'scheduled_start' => $interval->heure_fin,
            'event_type' => 'logout',
            'actual_login_at' => $logoutTime,
            'actual_logout_at' => $logoutTime,
            'status' => 'pending',
            'description' => "Déconnexion anticipée de {$hoursEarly} heure(s) et {$minutesRemaining} minute(s) avant la fin de journée",
        ]);
        // Notifier les administrateurs pour validation (comme pour les connexions tardives)
        $adminUsers = \App\Models\User::whereIn('role', ['admin', 'super_admin'])->get();
        if ($adminUsers->isNotEmpty()) {
            try {
                Notification::send($adminUsers, new AdminValidationNotification(
                    'Validation de déduction salariale requise',
                    "Une nouvelle déduction salariale de {$deductionAmount} FCFA est en attente de validation pour l'utilisateur {$user->nom_utilisateur}.",
                    'Voir les déductions',
                    route('admin.dashboard')
                ));
            } catch (\Throwable $e) {
                // Ne jamais bloquer la déconnexion à cause d'une notification
                report($e);
            }
        }
    }
}
