<?php

namespace DevWizard\Payify\Commands;

use DevWizard\Payify\Contracts\SupportsAuthCapture;
use DevWizard\Payify\Managers\PayifyManager;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Console\Command;

class VoidCommand extends Command
{
    protected $signature = 'payify:void {transaction_id}';

    protected $description = 'Void a previously authorized Payify transaction';

    public function handle(PayifyManager $manager): int
    {
        $txn = Transaction::find($this->argument('transaction_id'));
        if (! $txn) {
            $this->error('Transaction not found.');

            return self::FAILURE;
        }

        $driver = $manager->provider($txn->provider);

        if (! $driver instanceof SupportsAuthCapture) {
            $this->error("Provider [{$txn->provider}] does not support void.");

            return self::FAILURE;
        }

        $driver->void($txn);

        $this->info("Voided {$txn->id}.");

        return self::SUCCESS;
    }
}
