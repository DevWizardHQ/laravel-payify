<?php

namespace DevWizard\Payify;

use DevWizard\Payify\Http\PayifyHttpClient;
use DevWizard\Payify\Managers\PayifyManager;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PayifyServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-payify')
            ->hasConfigFile('payify')
            ->hasMigration('create_payify_transactions_table');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(PayifyHttpClient::class, function ($app) {
            return new PayifyHttpClient(
                $app['config']->get('payify.http', []),
                $app['log']->channel($app['config']->get('payify.log_channel')),
            );
        });

        $this->app->singleton('payify', function ($app) {
            return new PayifyManager($app);
        });

        $this->app->alias('payify', PayifyManager::class);
    }

    public function packageBooted(): void
    {
        $this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        if (! $this->app['config']->get('payify.routes.enabled', true)) {
            return;
        }

        if (Payify::hasCustomRoutes()) {
            return;
        }

        Route::group([
            'prefix' => $this->app['config']->get('payify.routes.prefix', 'payify'),
            'middleware' => $this->app['config']->get('payify.routes.middleware', ['api']),
            'domain' => $this->app['config']->get('payify.routes.domain'),
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/payify.php');
        });
    }
}
