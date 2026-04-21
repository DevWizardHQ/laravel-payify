<?php

namespace DevWizard\Payify\Support;

use Illuminate\Support\Str;

class ReferenceGenerator
{
    public static function make(string $prefix = 'PAY', int $length = 16): string
    {
        $random = Str::upper(Str::random($length));

        return "{$prefix}-{$random}";
    }
}
