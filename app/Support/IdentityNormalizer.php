<?php

namespace App\Support;

final class IdentityNormalizer
{
    public static function branchCode(mixed $value): ?string
    {
        return self::upper($value, false);
    }

    public static function vehiclePlate(mixed $value): ?string
    {
        return self::upper($value, true);
    }

    public static function serialNumber(mixed $value): ?string
    {
        return self::upper($value, false);
    }

    public static function email(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_strtolower($value);
    }

    public static function indonesianPhone(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', trim((string) $value)) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '62')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return '+62'.$digits;
    }

    private static function upper(mixed $value, bool $collapseWhitespace): ?string
    {
        $value = trim((string) $value);
        if ($collapseWhitespace) {
            $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        }

        return $value === '' ? null : mb_strtoupper($value);
    }
}
