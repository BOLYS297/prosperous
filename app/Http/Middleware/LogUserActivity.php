<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class LogUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = Auth::user();

        // Log seulement si ce n'est pas un super admin et que l'action modifie des données (POST, PUT, DELETE, PATCH)
        if ($user && $user->role !== 'super_admin' && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $routeName = $request->route() ? $request->route()->getName() : null;

            \App\Models\LogActivite::create([
                'user_id' => $user->id,
                'action' => $this->humanReadableAction($routeName, $request),
                'description' => $this->humanReadableDescription($routeName, $request),
            ]);
        }

        return $response;
    }

    private function humanReadableAction(?string $routeName, Request $request): string
    {
        if (!$routeName) {
            return ucfirst($request->method()) . ' ' . ucfirst(trim(str_replace('/', ' ', $request->path())));
        }

        return match (true) {
            str_contains($routeName, 'admin.rapports.depenses.approve') => 'Validation de dépense',
            str_contains($routeName, 'admin.rapports.depenses.reject') => 'Rejet de dépense',
            str_contains($routeName, 'admin.rapports.pertes.approve') => 'Validation de perte',
            str_contains($routeName, 'admin.rapports.pertes.reject') => 'Rejet de perte',
            str_contains($routeName, 'admin.users') => 'Gestion des utilisateurs',
            str_contains($routeName, 'admin.produits') => 'Gestion des produits',
            str_contains($routeName, 'admin.fournisseurs') => 'Gestion des fournisseurs',
            str_contains($routeName, 'admin.grossistes') => 'Gestion des grossistes',
            str_contains($routeName, 'admin.achats') => 'Gestion des achats',
            str_contains($routeName, 'magasinier.depenses.store') => 'Déclaration de perte',
            str_contains($routeName, 'magasinier.transferts.expedier') => 'Expédition de transfert',
            str_contains($routeName, 'magasinier.transferts.index') => 'Consultation des transferts',
            str_contains($routeName, 'boutiquier.transferts.probleme') => 'Signalement de transfert',
            str_contains($routeName, 'boutiquier.ventes') => 'Enregistrement de vente',
            default => ucfirst(str_replace(['.', '_'], [' ', ' '], $routeName)),
        };
    }

    private function humanReadableDescription(?string $routeName, Request $request): string
    {
        if ($routeName && str_contains($routeName, 'admin.rapports.depenses.approve')) {
            return 'Dépense validée par l’administrateur.';
        }

        if ($routeName && str_contains($routeName, 'admin.rapports.depenses.reject')) {
            return 'Dépense rejetée par l’administrateur.';
        }

        if ($routeName && str_contains($routeName, 'admin.rapports.pertes.approve')) {
            return 'Perte validée par l’administrateur.';
        }

        if ($routeName && str_contains($routeName, 'admin.rapports.pertes.reject')) {
            return 'Perte rejetée par l’administrateur.';
        }

        if ($routeName && str_contains($routeName, 'boutiquier.ventes.store')) {
            return 'Vente enregistrée pour le point de vente.';
        }

        if ($routeName && str_contains($routeName, 'magasinier.depenses.store')) {
            return 'Perte de stock déclarée par le magasinier.';
        }

        if ($routeName && str_contains($routeName, 'magasinier.transferts.expedier')) {
            return 'Demande de transfert expédiée vers la boutique.';
        }

        if ($routeName && str_contains($routeName, 'boutiquier.transferts.probleme')) {
            return 'Problème signalé sur une demande de transfert.';
        }

        if ($routeName) {
            $friendly = ucfirst(str_replace(['.', '_'], [' ', ' '], $routeName));
            return 'Action : ' . $friendly . '.';
        }

        return 'Requête ' . $request->method() . ' sur ' . str_replace('/', ' / ', $request->path()) . '.';
    }
}
