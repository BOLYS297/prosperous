<?php

use App\Models\Setting;

if (! function_exists('param')) {
    /**
     * Récupère un réglage d'entreprise (module Paramètres) avec repli.
     */
    function param(string $key, $default = null)
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('param_image')) {
    /**
     * URL d'une image de réglage (logo, bannière) avec repli sur un asset.
     */
    function param_image(string $key, string $fallbackAsset): string
    {
        return Setting::imageUrl($key, $fallbackAsset);
    }
}

if (! function_exists('money_format_app')) {
    /**
     * Formate un montant avec la devise configurée. Ex : 50 000 FCFA.
     */
    function money_format_app($montant): string
    {
        return number_format((float) $montant, 0, ',', ' ') . ' ' . Setting::get('currency', 'FCFA');
    }
}
