<?php

namespace DevWizard\Payify\Support;

class AmountFormatter
{
    private const ZERO_DECIMAL = [
        'JPY', 'KRW', 'VND', 'CLP', 'ISK', 'UGX', 'XAF', 'XOF',
    ];

    public static function toMinor(float $amount, string $currency): int
    {
        $exponent = self::exponent($currency);

        return (int) round($amount * (10 ** $exponent) + 1e-9, 0, PHP_ROUND_HALF_UP);
    }

    public static function toMajor(int $minor, string $currency): float
    {
        $exponent = self::exponent($currency);

        return $minor / (10 ** $exponent);
    }

    private static function exponent(string $currency): int
    {
        return in_array(strtoupper($currency), self::ZERO_DECIMAL, true) ? 0 : 2;
    }
}
