<?php

namespace App\Support;

class Money
{
    public static function decimals(string $currency): int
    {
        return (int) config("events.currencies.{$currency}.decimals", 2);
    }

    public static function symbol(string $currency): string
    {
        return (string) config("events.currencies.{$currency}.symbol", $currency.' ');
    }

    public static function format(int $minor, string $currency): string
    {
        $decimals = self::decimals($currency);

        return self::symbol($currency).number_format($minor / (10 ** $decimals), $decimals);
    }

    public static function toMinor(int|float|string $amount, string $currency): int
    {
        $decimals = self::decimals($currency);

        return (int) round(((float) $amount) * (10 ** $decimals));
    }

    public static function toMajor(int $minor, string $currency): float
    {
        return $minor / (10 ** self::decimals($currency));
    }
}
