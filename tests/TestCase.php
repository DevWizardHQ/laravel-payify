<?php

namespace DevWizard\Payify\Tests;

use DevWizard\Payify\Drivers\FakeDriver;
use DevWizard\Payify\Payify;
use DevWizard\Payify\PayifyServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function tearDown(): void
    {
        Payify::resetCustomRoutes();
        parent::tearDown();
    }

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
        config()->set('payify.routes.middleware', []);
        config()->set('payify.providers.fake', [
            'driver' => FakeDriver::class,
            'mode' => 'sandbox',
            'credentials' => [],
        ]);

        $migration = include __DIR__.'/../database/migrations/create_payify_transactions_table.php.stub';
        $migration->up();

        $phase2 = include __DIR__.'/../database/migrations/extend_payify_transactions_for_phase2.php.stub';
        $phase2->up();
    }
}
