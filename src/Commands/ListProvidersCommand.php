<?php

namespace DevWizard\Payify\Commands;

use DevWizard\Payify\Managers\PayifyManager;
use Illuminate\Console\Command;

class ListProvidersCommand extends Command
{
    protected $signature = 'payify:list';

    protected $description = 'List configured Payify providers with capabilities';

    public function handle(PayifyManager $manager): int
    {
        $providers = config('payify.providers', []);

        if ($providers === []) {
            $this->warn('No providers configured. Edit config/payify.php to add one.');
            return self::SUCCESS;
        }

        $rows = [];
        foreach ($providers as $key => $config) {
            $class = $config['driver'] ?? '(missing)';
            $mode = $config['mode'] ?? config('payify.mode', 'sandbox');

            $caps = '';
            $credsOk = '✗';
            try {
                $driver = $manager->provider((string) $key);
                $c = $driver->capabilities();
                $caps = collect([
                    'refund' => $c['refund'] ?? false,
                    'token' => $c['tokenization'] ?? false,
                    'webhook' => $c['webhook'] ?? false,
                    'hosted' => $c['hosted_checkout'] ?? false,
                    'direct' => $c['direct_api'] ?? false,
                ])->filter()->keys()->join(', ');
                $credsOk = '✓';
            } catch (\Throwable $e) {
                $caps = '(resolution error)';
            }

            $rows[] = [$key, $class, $mode, $caps, $credsOk];
        }

        $this->table(['Provider', 'Class', 'Mode', 'Capabilities', 'Resolves'], $rows);

        return self::SUCCESS;
    }
}
