<?php

namespace DevWizard\Payify;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use DevWizard\Payify\Commands\PayifyCommand;

class PayifyServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-payify')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_payify_table')
            ->hasCommand(PayifyCommand::class);
    }
}
