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
    })->create();
