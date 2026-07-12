<?php

namespace App\Providers;

use App\Models\DemandeTransfert;
use App\Models\Recharge;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        View::composer('layouts.magasinier', function ($view) {
            $pendingRequestsCount = cache()->remember('magasinier_pending_requests_count', 30, function () {
                return DemandeTransfert::where('statut', 'en_attente')->count();
            });

            $asideRechargeCount = 0;

            if (Auth::check() && Auth::user()->boutique) {
                $boutiqueId = Auth::user()->boutique->id;
                $asideRechargeCount = cache()->remember("magasinier_aside_recharge_count_{$boutiqueId}", 30, function () use ($boutiqueId) {
                    return Recharge::where('destination_id', $boutiqueId)
                        ->where('statut', 'en_attente')
                        ->count();
                });
            }

            $view->with(compact('pendingRequestsCount', 'asideRechargeCount'));
        });
    }
}
