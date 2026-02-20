<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected static function booted()
    {
        static::updated(function ($setting) {
            \Illuminate\Support\Facades\Cache::forget('app_settings');
        });

        static::created(function ($setting) {
            \Illuminate\Support\Facades\Cache::forget('app_settings');
        });

        static::deleted(function ($setting) {
            \Illuminate\Support\Facades\Cache::forget('app_settings');
        });
    }

    public static function getAllCached()
    {
        return \Illuminate\Support\Facades\Cache::remember('app_settings', 86400, function () {
            return static::pluck('value', 'key')->toArray();
        });
    }

    public static function getCached($key, $default = null)
    {
        $settings = static::getAllCached();

        return $settings[$key] ?? $default;
    }

    public static function getCurrencySymbol()
    {
        $symbol = static::getCached('currency_symbol');
        $code = static::getCached('currency_code', 'USD');

        if (!$symbol || $symbol === $code) {
            $mapping = [
                'USD' => '$',
                'INR' => '₹',
                'EUR' => '€',
                'GBP' => '£',
                'AUD' => 'A$',
                'CAD' => 'C$',
                'JPY' => '¥',
            ];
            return $mapping[$code] ?? $code;
        }

        return $symbol;
    }
}
