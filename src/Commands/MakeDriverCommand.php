<?php

namespace DevWizard\Payify\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeDriverCommand extends Command
{
    protected $signature = 'payify:make-driver {name : Driver class name (e.g. Paddle)} {--force : Overwrite if the file exists}';

    protected $description = 'Scaffold a new Payify driver under app/Payify/Drivers';

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $class = Str::endsWith($name, 'Driver') ? $name : $name.'Driver';
        $key = Str::snake(Str::replaceLast('Driver', '', $class));
        $envPrefix = Str::upper($key);

        $namespace = 'App\\Payify\\Drivers';
        $directory = app_path('Payify/Drivers');
        $path = $directory.'/'.$class.'.php';

        File::ensureDirectoryExists($directory);

        if (File::exists($path) && ! $this->option('force')) {
            $this->error("Driver already exists at [{$path}]. Use --force to overwrite.");
            return self::FAILURE;
        }

        $stub = File::get(__DIR__.'/../Stubs/driver.stub');
        $content = strtr($stub, [
            '{{ namespace }}' => $namespace,
            '{{ class }}' => $class,
            '{{ key }}' => $key,
            '{{ env_prefix }}' => $envPrefix,
        ]);

        File::put($path, $content);

        $this->info("Driver created at {$path}");
        $this->line("Register it in config/payify.php under providers.{$key}.");

        return self::SUCCESS;
    }
}
