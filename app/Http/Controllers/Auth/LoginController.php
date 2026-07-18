<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AdminValidationNotification;
use App\Models\Deduction;
use App\Models\DeductionSetting;
use App\Models\HoraireConnexion;
use App\Models\LogActivite;
use App\Models\User;

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
                                    "Une nouvelle déduction salariale de " . money_format_app($deduction->amount) . " est en attente de validation pour l'utilisateur {$user->nom_utilisateur}.",
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

            // Rattrapage : si, lors de sa dernière présence (un jour passé), l'employé
            // a quitté l'application AVANT la fin de sa session sans se déconnecter,
            // on crée maintenant la déduction de départ anticipé qui avait été
            // manquée faute de clic « Déconnexion ».
            if (in_array($user->role, ['magasinier', 'boutiquier'], true)) {
                $this->rattraperDepartManque($user);
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

    /**
     * Départ anticipé sur déconnexion explicite : l'employé clique « Déconnexion »
     * avant la fin de sa session en cours.
     */
    protected function createEarlyLogoutDeduction($user, Carbon $logoutTime): void
    {
        $session = HoraireConnexion::getCurrentIntervalForUser($user);

        if ($session) {
            $this->enregistrerDepartAnticipe($user, $session, $logoutTime);
        }
    }

    /**
     * Rattrapage du départ anticipé « silencieux » (l'employé a fermé
     * l'application sans se déconnecter). À la connexion suivante, on regarde sa
     * DERNIÈRE présence : si elle date d'un jour passé et tombe à l'intérieur
     * d'une session (donc avant sa fin), le départ anticipé correspondant est
     * enregistré rétroactivement à l'heure de cette dernière présence.
     */
    protected function rattraperDepartManque(User $user): void
    {
        if (! $user->last_seen_at) {
            return;
        }

        $lastSeen = Carbon::parse($user->last_seen_at);

        // On ne traite qu'un jour PASSÉ : la session du jour même est encore en
        // cours (ou sera soldée par la déconnexion / un futur rattrapage).
        if ($lastSeen->isToday()) {
            return;
        }

        // Session active à l'instant de la dernière présence (même jour de semaine
        // + heure comprise dans la tranche). Null = dernière présence hors session
        // (heures supp ou repos) : aucun départ anticipé à imputer.
        $session = HoraireConnexion::sessionAt($user, $lastSeen);

        if ($session) {
            $this->enregistrerDepartAnticipe($user, $session, $lastSeen);
        }
    }

    /**
     * Cœur commun : crée une déduction de départ anticipé pour la session donnée
     * si l'heure de départ est antérieure à la fin de session. Idempotent : une
     * seule déduction de départ par employé et par jour.
     */
    protected function enregistrerDepartAnticipe(User $user, HoraireConnexion $session, Carbon $departTime): void
    {
        $scheduledEnd = Carbon::createFromFormat('H:i:s', $session->heure_fin, $departTime->getTimezone())
            ->setDate($departTime->year, $departTime->month, $departTime->day);

        // Parti à l'heure ou plus tard : rien à pénaliser.
        if (! $departTime->lt($scheduledEnd)) {
            return;
        }

        $hourlyAmount = DeductionSetting::getHourlyAmount();
        if ($hourlyAmount <= 0) {
            return;
        }

        $minutesEarly = (int) $departTime->diffInMinutes($scheduledEnd);
        if ($minutesEarly <= 0) {
            return;
        }

        // Anti-doublon : au plus une déduction de départ anticipé par jour, quel
        // que soit son statut (protège aussi contre une double déconnexion).
        $dejaEnregistre = Deduction::where('user_id', $user->id)
            ->where('event_type', 'logout')
            ->whereDate('actual_login_at', $departTime->toDateString())
            ->exists();
        if ($dejaEnregistre) {
            return;
        }

        $deductionAmount = (int) round(($minutesEarly / 60.0) * $hourlyAmount);
        $hoursEarly = intdiv($minutesEarly, 60);
        $minutesRemaining = $minutesEarly % 60;

        Deduction::create([
            'user_id' => $user->id,
            'amount' => $deductionAmount,
            'minutes_late' => $minutesEarly,
            'scheduled_start' => $session->heure_fin,
            'event_type' => 'logout',
            'actual_login_at' => $departTime,
            'actual_logout_at' => $departTime,
            'status' => 'pending',
            'description' => "Départ anticipé de {$hoursEarly} heure(s) et {$minutesRemaining} minute(s) avant la fin de journée",
        ]);

        // Notifier les administrateurs pour validation (comme pour les retards)
        $adminUsers = User::whereIn('role', ['admin', 'super_admin'])->get();
        if ($adminUsers->isNotEmpty()) {
            try {
                Notification::send($adminUsers, new AdminValidationNotification(
                    'Validation de déduction salariale requise',
                    "Une nouvelle déduction salariale de " . money_format_app($deductionAmount) . " est en attente de validation pour l'utilisateur {$user->nom_utilisateur}.",
                    'Voir les déductions',
                    route('admin.dashboard')
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
