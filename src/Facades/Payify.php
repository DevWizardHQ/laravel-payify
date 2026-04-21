<?php

namespace DevWizard\Payify\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \DevWizard\Payify\Payify
 */
class Payify extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \DevWizard\Payify\Payify::class;
    }
}
