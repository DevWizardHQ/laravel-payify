<?php

namespace DevWizard\Payify\Tests;

use DevWizard\Payify\Http\PayifyHttpClient;
use DevWizard\Payify\Managers\PayifyManager;
use DevWizard\Payify\PayifyServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'DevWizard\\Payify\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            PayifyServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        config()->set('app.debug', false);

        $migration = include __DIR__.'/../database/migrations/create_payify_transactions_table.php.stub';
        $migration->up();

        $app->singleton(PayifyHttpClient::class, function ($app) {
            $config = config('payify.http', ['timeout' => 1, 'retries' => 0, 'retry_delay' => 1, 'mask_keys' => [], 'log_requests' => false]);

            return new PayifyHttpClient($config, $app['log']->getLogger());
        });
        $app->singleton('payify', function ($app) {
            return new PayifyManager($app);
        });
        $app->alias('payify', PayifyManager::class);
    }

    protected function defineRoutes($router): void
    {
        $router->group([
            'prefix' => 'payify',
            'middleware' => config('payify.routes.middleware', []),
        ], function ($router) {
            require __DIR__.'/../routes/payify.php';
        });
    }
}
