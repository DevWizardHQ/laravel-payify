<?php

namespace DevWizard\Payify;

use DevWizard\Payify\Commands\AgreementCancelCommand;
use DevWizard\Payify\Commands\AgreementListCommand;
use DevWizard\Payify\Commands\CaptureCommand;
use DevWizard\Payify\Commands\CleanupCommand;
use DevWizard\Payify\Commands\InstallCommand;
use DevWizard\Payify\Commands\ListProvidersCommand;
use DevWizard\Payify\Commands\MakeDriverCommand;
use DevWizard\Payify\Commands\PayoutCommand;
use DevWizard\Payify\Commands\RefundCommand;
use DevWizard\Payify\Commands\RefundStatusCommand;
use DevWizard\Payify\Commands\StatusCommand;
use DevWizard\Payify\Commands\VoidCommand;
use DevWizard\Payify\Commands\WebhookReplayCommand;
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
            ->hasMigration('create_payify_transactions_table')
            ->hasMigration('extend_payify_transactions_for_phase2')
            ->hasMigration('create_payify_agreements_table')
            ->hasCommands([
                InstallCommand::class,
                MakeDriverCommand::class,
                ListProvidersCommand::class,
                StatusCommand::class,
                RefundCommand::class,
                WebhookReplayCommand::class,
                CleanupCommand::class,
                CaptureCommand::class,
                VoidCommand::class,
                AgreementListCommand::class,
                AgreementCancelCommand::class,
                PayoutCommand::class,
                RefundStatusCommand::class,
            ]);
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

        $group = array_filter([
            'prefix' => $this->app['config']->get('payify.routes.prefix', 'payify'),
            'middleware' => $this->app['config']->get('payify.routes.middleware', ['api']),
            'domain' => $this->app['config']->get('payify.routes.domain'),
        ], fn ($v) => $v !== null);

        Route::group($group, function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/payify.php');
        });
    }
}
