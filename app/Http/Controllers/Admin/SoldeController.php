<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSoldeMouvement;
use App\Models\AvanceCaisse;
use App\Models\Boutique;
use App\Models\DebitValidation;
use App\Models\LogActivite;
use App\Models\User;
use App\Notifications\PendingActionNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SoldeController extends Controller
{
    /** Solde personnel + historique des mouvements + récupération des recettes. */
    public function index()
    {
        $admin = Auth::user();

        $mouvements = AdminSoldeMouvement::with('boutique')
            ->where('admin_id', $admin->id)
            ->orderByDesc('created_at')
            ->paginate(25);

        $boutiques = Boutique::orderBy('type')->orderBy('nom')->get();

        // Recettes déjà demandées mais pas encore validées par les boutiques.
        $recettesEnAttente = DebitValidation::with('boutique')
            ->where('source_type', 'recette')
            ->where('initiator_id', $admin->id)
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();

        $totaux = [
            'recettes' => (float) AdminSoldeMouvement::where('admin_id', $admin->id)->where('type', 'recette')->sum('montant'),
            'sorties' => (float) abs(AdminSoldeMouvement::where('admin_id', $admin->id)->where('montant', '<', 0)->sum('montant')),
        ];

        // Achats à crédit imputés à mon solde personnel, encore à rembourser.
        $dettesAdmin = \App\Models\Achat::with(['fournisseur', 'paiements'])
            ->where('debit_admin_id', $admin->id)
            ->where('statut', 'dette')
            ->get()
            ->filter(fn ($achat) => $achat->reste_a_payer > 0);

        // Avances personnelles encore à rembourser depuis le solde personnel.
        $avancesAdmin = AvanceCaisse::dettesAdmin($admin->id)
            ->where('statut', 'en_cours')
            ->orderBy('created_at')
            ->get();

        $avancesRestant = $avancesAdmin->sum(fn ($a) => $a->reste_a_rembourser);

        return view('admin.solde.index', compact('admin', 'mouvements', 'boutiques', 'recettesEnAttente', 'totaux', 'dettesAdmin', 'avancesAdmin', 'avancesRestant'));
    }

    /**
     * Récupération des recettes : pour chaque point de vente choisi, on crée une
     * demande de validation. Le solde de la boutique ne sera débité (et le solde
     * personnel crédité) qu'après validation du boutiquier.
     */
    public function recuperer(Request $request)
    {
        $validated = $request->validate([
            'recettes' => 'required|array|min:1',
            'recettes.*.boutique_id' => 'required|exists:boutiques,id',
            'recettes.*.montant' => 'required|numeric|min:1',
            'motif' => 'nullable|string|max:255',
        ]);

        $motif = trim($validated['motif'] ?? '') ?: 'Récupération des recettes';
        $created = 0;
        $ignorees = [];

        foreach ($validated['recettes'] as $ligne) {
            $boutique = Boutique::find($ligne['boutique_id']);
            if (! $boutique) {
                continue;
            }

            $montant = (float) $ligne['montant'];

            // On ne demande jamais plus que la trésorerie disponible.
            if ($montant > (float) $boutique->solde) {
                $ignorees[] = $boutique->nom;
                continue;
            }

            DebitValidation::create([
                'boutique_id' => $boutique->id,
                'initiator_id' => Auth::id(),
                'amount' => $montant,
                'source_type' => 'recette',
                'source_id' => null,
                'motif' => $motif,
                'status' => 'pending',
            ]);

            $this->notifyBoutiquiers($boutique, $montant, $motif);
            $created++;
        }

        if ($created === 0) {
            return back()->with('error', 'Aucune demande créée : les montants dépassent la trésorerie disponible.');
        }

        LogActivite::create([
            'user_id' => Auth::id(),
            'action' => 'admin.solde.recuperer',
            'description' => "Demande de récupération des recettes envoyée à {$created} point(s) de vente ({$motif}).",
        ]);

        $message = "Demande envoyée à {$created} point(s) de vente. Le solde sera débité après validation du boutiquier.";
        if (! empty($ignorees)) {
            $message .= ' Ignoré (montant > trésorerie) : ' . implode(', ', $ignorees) . '.';
        }

        return redirect()->route('admin.solde.index')->with('success', $message);
    }

    /**
     * Approvisionner son propre solde personnel en cash, en deux modes :
     *  - simple : apport définitif, rien à rembourser ;
     *  - dette  : le solde est crédité MAIS une avance est ouverte, à rembourser
     *             progressivement depuis ce même solde (comme pour une boutique).
     */
    public function crediter(Request $request)
    {
        $validated = $request->validate([
            'montant' => 'required|numeric|min:1',
            'motif' => 'nullable|string|max:255',
            'mode' => 'required|in:simple,dette',
        ]);

        $admin = Auth::user();
        $montant = round((float) $validated['montant'], 2);
        $isDette = $validated['mode'] === 'dette';
        $motif = trim($validated['motif'] ?? '') ?: ($isDette ? 'Avance à rembourser' : 'Approvisionnement en cash');

        DB::transaction(function () use ($admin, $montant, $motif, $isDette) {
            $avance = null;

            if ($isDette) {
                // boutique_id null = dette portée par l'admin lui-même.
                $avance = AvanceCaisse::create([
                    'boutique_id' => null,
                    'admin_id' => $admin->id,
                    'montant' => $montant,
                    'montant_rembourse' => 0,
                    'motif' => $motif,
                    'statut' => 'en_cours',
                ]);
            }

            AdminSoldeMouvement::enregistrer(
                $admin->id,
                $isDette ? 'avance' : 'approvisionnement',
                $montant,
                $motif,
                null,
                $isDette ? 'avance_caisse' : null,
                $avance?->id
            );

            LogActivite::create([
                'user_id' => $admin->id,
                'action' => $isDette ? 'admin.solde.avance' : 'admin.solde.crediter',
                'description' => ($isDette ? 'Avance de ' : 'Approvisionnement de ')
                    . money_format_app($montant) . " sur le solde personnel ({$motif}).",
            ]);
        });

        return back()->with('success', $isDette
            ? 'Avance de ' . money_format_app($montant) . ' créditée sur votre solde, à rembourser progressivement.'
            : 'Votre solde personnel a été crédité de ' . money_format_app($montant) . '.');
    }

    /**
     * Rembourser (tout ou partie d') une avance personnelle depuis le solde
     * personnel. Verrous : le remboursement ne peut jamais être compté deux fois.
     */
    public function rembourserAvance(Request $request, AvanceCaisse $avance)
    {
        $request->validate([
            'montant' => 'required|numeric|min:1',
        ]);

        $admin = Auth::user();

        if ($avance->boutique_id !== null || (int) $avance->admin_id !== (int) $admin->id) {
            return back()->with('error', 'Cette avance ne vous est pas imputée.');
        }

        $montant = round((float) $request->input('montant'), 2);

        $dejaSoldee = false;
        $tropGrand = false;
        $soldeInsuffisant = false;

        DB::transaction(function () use ($avance, $admin, $montant, &$dejaSoldee, &$tropGrand, &$soldeInsuffisant) {
            $fresh = AvanceCaisse::where('id', $avance->id)->lockForUpdate()->first();

            if (! $fresh || $fresh->statut !== 'en_cours') {
                $dejaSoldee = true;
                return;
            }

            if ($montant > $fresh->reste_a_rembourser) {
                $tropGrand = true;
                return;
            }

            $frais = User::where('id', $admin->id)->lockForUpdate()->first();
            if ($montant > (float) $frais->solde_personnel) {
                $soldeInsuffisant = true;
                return;
            }

            AdminSoldeMouvement::enregistrer(
                $admin->id,
                'remboursement',
                -$montant,
                "Remboursement de l'avance #{$fresh->id}",
                null,
                'avance_caisse',
                $fresh->id
            );

            $fresh->increment('montant_rembourse', $montant);
            $fresh->refresh();

            if ($fresh->reste_a_rembourser <= 0) {
                $fresh->update(['statut' => 'remboursee']);
            }
        });

        if ($dejaSoldee) {
            return back()->with('error', 'Cette avance est déjà entièrement remboursée.');
        }
        if ($tropGrand) {
            return back()->with('error', 'Le montant dépasse le reste à rembourser.');
        }
        if ($soldeInsuffisant) {
            return back()->with('error', 'Solde personnel insuffisant pour ce remboursement.');
        }

        return back()->with('success', 'Remboursement de ' . money_format_app($montant) . ' enregistré.');
    }

    /** Vider (totalement ou partiellement) le solde personnel. */
    public function retirer(Request $request)
    {
        $validated = $request->validate([
            'montant' => 'required|numeric|min:1',
            'motif' => 'nullable|string|max:255',
        ]);

        $admin = Auth::user();
        $montant = (float) $validated['montant'];

        if ($montant > (float) $admin->solde_personnel) {
            return back()->with('error', 'Le montant dépasse votre solde personnel.');
        }

        AdminSoldeMouvement::enregistrer(
            $admin->id,
            'retrait',
            -$montant,
            trim($validated['motif'] ?? '') ?: 'Retrait du solde personnel'
        );

        LogActivite::create([
            'user_id' => $admin->id,
            'action' => 'admin.solde.retrait',
            'description' => 'Retrait de ' . money_format_app($montant) . ' du solde personnel.',
        ]);

        return back()->with('success', 'Retrait de ' . money_format_app($montant) . ' enregistré.');
    }

    /**
     * Rembourser (tout ou partie d') un achat à crédit imputé au solde personnel.
     */
    public function rembourserAchat(Request $request, \App\Models\Achat $achat)
    {
        $request->validate([
            'montant' => 'required|numeric|min:1',
        ]);

        $admin = Auth::user();

        if ((int) $achat->debit_admin_id !== (int) $admin->id || $achat->statut !== 'dette') {
            return back()->with('error', 'Cette dette ne vous est pas imputée ou est déjà réglée.');
        }

        $montant = round((float) $request->input('montant'), 2);

        if ($montant > $achat->reste_a_payer) {
            return back()->with('error', 'Le montant dépasse le reste à payer.');
        }

        if ($montant > (float) $admin->solde_personnel) {
            return back()->with('error', 'Solde personnel insuffisant.');
        }

        \App\Models\AchatPaiement::create([
            'achat_id' => $achat->id,
            'boutique_id' => null,
            'user_id' => $admin->id,
            'montant' => $montant,
            'description' => 'Remboursement depuis le solde personnel de l\'administrateur',
        ]);

        AdminSoldeMouvement::enregistrer(
            $admin->id,
            'remboursement',
            -$montant,
            "Remboursement de l'achat #{$achat->id}",
            null,
            'achat',
            $achat->id
        );

        $achat->refresh();
        if ($achat->reste_a_payer <= 0) {
            $achat->update(['statut' => 'paye']);
        }

        return back()->with('success', 'Remboursement de ' . money_format_app($montant) . ' enregistré.');
    }

    protected function notifyBoutiquiers(Boutique $boutique, float $montant, string $motif): void
    {
        $recipients = User::where('role', 'boutiquier')->where('boutique_id', $boutique->id)->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new PendingActionNotification(
            'Récupération de recette à valider',
            "L'administrateur souhaite récupérer " . money_format_app($montant) . " de votre caisse ({$motif}). Validez pour confirmer la remise.",
            'Valider',
            route('boutiquier.dashboard'),
            ['type' => 'debit_validation', 'montant' => $montant]
        ));
    }
}
