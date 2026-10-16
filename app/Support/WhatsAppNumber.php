<?php

namespace App\Support;

class WhatsAppNumber
{
    public const DEFAULT_COUNTRY = 'nigeria';

    /** @var array<string, string> */
    private const DIALING_CODES = [
        'nigeria' => '234',
        'ghana' => '233',
        'kenya' => '254',
        'south africa' => '27',
        'cameroon' => '237',
        'benin' => '229',
        'togo' => '228',
        'united kingdom' => '44',
        'uk' => '44',
        'united states' => '1',
        'usa' => '1',
    ];

    /**
     * Returns digits-only international format, or null when a local number
     * cannot be converted safely (unknown country or unusable input).
     */
    public static function normalize(?string $number, ?string $country = null): ?string
    {
        $number = trim((string) $number);

        if ($number === '') {
            return null;
        }

        $international = str_starts_with($number, '+');
        $digits = preg_replace('/\D+/', '', $number);

        if ($digits === '' || $digits === null) {
            return null;
        }

        if ($international) {
            return $digits;
        }

        if (str_starts_with($digits, '00')) {
            return substr($digits, 2) ?: null;
        }

        if (! str_starts_with($digits, '0')) {
            return $digits;
        }

        $code = self::dialingCode($country);

        if ($code === null) {
            return null;
        }

        $local = ltrim($digits, '0');

        return $local === '' ? null : $code . $local;
    }

    public static function dialingCode(?string $country): ?string
    {
        $key = strtolower(trim((string) $country));

        if ($key === '') {
            $key = self::DEFAULT_COUNTRY;
        }

        return self::DIALING_CODES[$key] ?? null;
    }
}
