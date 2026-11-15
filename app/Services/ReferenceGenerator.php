<?php

namespace App\Services;

use Illuminate\Support\Str;

class ReferenceGenerator
{
    public static function order(): string
    {
        return 'ORD-'.Str::upper(Str::random(8));
    }

    public static function attendee(): string
    {
        return 'TKT-'.Str::upper(Str::random(10));
    }
}
