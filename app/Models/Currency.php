<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'name', 'symbol', 'exchange_rate', 'is_default'])]
class Currency extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:4',
            'is_default' => 'boolean',
        ];
    }

    /**
     * In-request memoization untuk default currency.
     * Tidak dipersistensi ke cache store (rentan __PHP_Incomplete_Class saat
     * Eloquent model di-unserialize lintas request dengan database cache driver).
     *
     * @var array{code:string,symbol:string,decimals:int}|null
     */
    private static ?array $defaultMeta = null;

    public static function default(): self
    {
        try {
            $row = self::where('is_default', true)->first()
                ?? self::orderBy('id')->first();
        } catch (\Throwable $e) {
            $row = null;
        }

        return $row ?? new self([
            'code' => 'IDR',
            'name' => 'Indonesia Rupiah',
            'symbol' => 'Rp',
            'exchange_rate' => 1,
            'is_default' => true,
        ]);
    }

    /**
     * Lightweight metadata only (string/int) — aman di-cache & di-serialize.
     */
    public static function defaultMeta(): array
    {
        if (self::$defaultMeta !== null) {
            return self::$defaultMeta;
        }

        $d = self::default();
        $code = $d->code ?: 'IDR';

        return self::$defaultMeta = [
            'code' => $code,
            'symbol' => $d->symbol ?: $code,
            'decimals' => $code === 'IDR' ? 0 : 2,
        ];
    }

    public static function format(float|int|null $amount, ?int $decimals = null): string
    {
        $meta = self::defaultMeta();
        $decimals ??= $meta['decimals'];
        return $meta['symbol'] . ' ' . number_format((float) ($amount ?? 0), $decimals, ',', '.');
    }

    protected static function booted(): void
    {
        static::saved(function () { self::$defaultMeta = null; });
        static::deleted(function () { self::$defaultMeta = null; });
    }
}
