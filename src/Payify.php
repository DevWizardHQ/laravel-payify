<?php

namespace DevWizard\Payify;

use DevWizard\Payify\Testing\PayifyFake;
use Illuminate\Support\Facades\Route;

class Payify
{
    private static bool $routesOverridden = false;

    public static function routes(array $options = []): void
    {
        if (self::$routesOverridden) {
            return;
        }

        self::$routesOverridden = true;

        $group = array_filter([
            'prefix' => $options['prefix'] ?? config('payify.routes.prefix', 'payify'),
            'middleware' => $options['middleware'] ?? config('payify.routes.middleware', ['api']),
            'domain' => $options['domain'] ?? config('payify.routes.domain'),
        ], fn ($v) => $v !== null);

        Route::group($group, function () {
            require __DIR__.'/../routes/payify.php';
        });
    }

    public static function hasCustomRoutes(): bool
    {
        return self::$routesOverridden;
    }

    public static function resetCustomRoutes(): void
    {
        self::$routesOverridden = false;
    }

    public static function fake(array|string $providers = []): PayifyFake
    {
        return PayifyFake::install($providers);
    }
}
