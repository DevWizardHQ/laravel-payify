<?php

namespace DevWizard\Payify\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'payify:install {--no-migrate : Skip running migrations}';

    protected $description = 'Publish Payify config + migrations and run them';

    public function handle(): int
    {
        $this->components->info('Publishing Payify config...');
        $this->call('vendor:publish', ['--tag' => 'laravel-payify-config']);

        $this->components->info('Publishing Payify migrations...');
        $this->call('vendor:publish', ['--tag' => 'laravel-payify-migrations']);

        if (! $this->option('no-migrate')) {
            if ($this->confirm('Run migrations now?', true)) {
                $this->call('migrate');
            }
        }

        $this->newLine();
        $this->components->info('Payify installed. Next steps:');
        $this->line('  1. Register drivers in config/payify.php under providers.');
        $this->line('  2. Set PAYIFY_DEFAULT in .env to your default provider.');
        $this->line('  3. Run php artisan payify:list to verify setup.');

        return self::SUCCESS;
    }
}
