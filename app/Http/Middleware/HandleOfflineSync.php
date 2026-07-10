<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fiabilise la synchronisation hors-ligne.
 *
 * Les actions rejouées par le client (ventes, dettes, transferts…) portent
 * l'en-tête "X-Offline-Sync". Les contrôleurs signalent leur résultat via un
 * flash + une redirection (back()->with('success'|'error')), ce qui renvoie un
 * 302 -> le client ne pouvait pas distinguer un vrai succès d'un échec métier,
 * et supprimait l'action de la file dans tous les cas (perte silencieuse).
 *
 * Ce middleware convertit la redirection en réponse JSON machine-lisible :
 *   - 200 {ok:true}   -> action réussie
 *   - 422 {ok:false}  -> échec métier/validation (à archiver, ne pas rejouer)
 *   - 401 {retry:true}-> session expirée (à réessayer plus tard)
 * Il n'agit QUE si l'en-tête X-Offline-Sync est présent : aucun impact sur les
 * requêtes normales.
 */
class HandleOfflineSync
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->headers->has('X-Offline-Sync')) {
            return $response;
        }

        if (! $response->isRedirection()) {
            // Ex. 422 JSON automatique de la validation (Accept: application/json),
            // ou 200/500 : on laisse tel quel, le client sait interpréter le statut.
            return $response;
        }

        $target = method_exists($response, 'getTargetUrl') ? $response->getTargetUrl() : '';

        // Redirigé vers la connexion => session expirée : NE PAS perdre, réessayer.
        if (str_contains($target, '/login')) {
            return response()->json([
                'ok' => false,
                'retry' => true,
                'message' => 'Session expirée, l\'action sera réessayée.',
            ], 401);
        }

        $session = $request->session();
        $errorsBag = $session->get('errors');
        $flashError = $session->get('error');
        $flashSuccess = $session->get('success');
        $session->forget(['error', 'success', 'errors']);

        if ($errorsBag && method_exists($errorsBag, 'any') && $errorsBag->any()) {
            return response()->json(['ok' => false, 'message' => $errorsBag->first()], 422);
        }

        if (! empty($flashError)) {
            return response()->json(['ok' => false, 'message' => $flashError], 422);
        }

        return response()->json(['ok' => true, 'message' => $flashSuccess], 200);
    }
}
