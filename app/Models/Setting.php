<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public const CACHE_KEY = 'app_settings';

    /**
     * Valeurs par défaut utilisées tant qu'aucun réglage n'est enregistré.
     * Sert aussi de liste blanche des clés éditables (hors images).
     */
    public const DEFAULTS = [
        'company_name' => 'Prosperous Motos',
        'company_immatriculation' => 'P019017879563S',
        'currency' => 'FCFA',
        'company_address' => '',
        'company_phone' => '',
        'ticket_footer' => 'Merci de votre visite !',
        // Pourcentage de commission proposé par défaut à la création d'un mécanicien.
        'mecanicien_commission_percent' => '10',
        'logo_path' => '',
        'banner_path' => '',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Tous les réglages (défauts fusionnés avec les valeurs stockées), en cache.
     *
     * @return array<string, string>
     */
    public static function allValues(): array
    {
        $stored = Cache::rememberForever(self::CACHE_KEY, function () {
            return static::query()->pluck('value', 'key')->toArray();
        });

        return array_merge(self::DEFAULTS, array_filter($stored, fn ($v) => $v !== null));
    }

    public static function get(string $key, $default = null)
    {
        $values = static::allValues();

        if (array_key_exists($key, $values) && $values[$key] !== '' && $values[$key] !== null) {
            return $values[$key];
        }

        return $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function set(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * URL publique d'une image de réglage (logo, bannière), avec repli sur un
     * asset embarqué si aucune image n'a été téléversée.
     */
    public static function imageUrl(string $key, string $fallbackAsset): string
    {
        $path = static::get($key);

        if ($path && is_string($path) && trim($path) !== '') {
            return asset('storage/' . ltrim($path, '/'));
        }

        return asset($fallbackAsset);
    }
}
