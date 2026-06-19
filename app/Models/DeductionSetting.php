<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeductionSetting extends Model
{
    protected $table = 'deduction_settings';

    protected $fillable = [
        'hourly_retard_amount',
        'top_products_count',
    ];

    public static function current(): self
    {
        return self::firstOrCreate([], [
            'hourly_retard_amount' => 0,
            'top_products_count' => 5,
        ]);
    }

    public static function getHourlyAmount(): int
    {
        return self::current()->hourly_retard_amount;
    }

    public static function getTopProductsCount(): int
    {
        return self::current()->top_products_count;
    }
}
