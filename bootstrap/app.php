<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        // using: function (): void {
        //     require __DIR__.'/../routes/admin.php';
        //     require __DIR__.'/../routes/client.php';
        // },
    )
    ->withExceptions()
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'check.shift' => \App\Http\Middleware\CheckShiftTime::class,
            'check.device' => \App\Http\Middleware\CheckDevice::class,
            'check.horaire' => \App\Http\Middleware\CheckHoraireConnexion::class,
            'log.activity' => \App\Http\Middleware\LogUserActivity::class,
        ]);

        // Derrière le reverse-proxy Caddy (HTTPS) : faire confiance au proxy pour
        // que Laravel détecte correctement le schéma HTTPS (cookies de session
        // sécurisés, génération d'URL, vraie IP client). Le conteneur app n'est
        // joignable que par Caddy, donc "*" est sûr ici.
        $middleware->trustProxies(at: '*');

        // Fiabilise la resynchronisation hors-ligne : convertit le résultat des
        // actions rejouées (en-tête X-Offline-Sync) en JSON succès/échec.
        $middleware->appendToGroup('web', \App\Http\Middleware\HandleOfflineSync::class);

        // Trace la dernière présence de l'employé (throttlé) pour détecter un
        // départ anticipé sans déconnexion.
        $middleware->appendToGroup('web', \App\Http\Middleware\UpdateLastSeen::class);
    })->create();
