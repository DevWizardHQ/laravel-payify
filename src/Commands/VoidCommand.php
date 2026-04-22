<?php

namespace DevWizard\Payify\Commands;

use DevWizard\Payify\Contracts\SupportsAuthCapture;
use DevWizard\Payify\Managers\PayifyManager;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;

class VoidCommand extends Command
{
    protected $signature = 'payify:void {transaction_id}';

    protected $description = 'Void a previously authorized Payify transaction';

    public function handle(PayifyManager $manager): int
    {
        $txn = Transaction::find((string) $this->argument('transaction_id'));
        if (! $txn) {
            error('Transaction not found.');

            return self::FAILURE;
        }

        $driver = $manager->provider($txn->provider);

        if (! $driver instanceof SupportsAuthCapture) {
            error("Provider [{$txn->provider}] does not support void.");

            return self::FAILURE;
        }

        if (! $this->confirm("Void transaction {$txn->id}?", false)) {
            return self::SUCCESS;
        }

        spin(fn () => $driver->void($txn), 'Voiding transaction...');

        info("Voided {$txn->id}.");

        return self::SUCCESS;
    }
}
