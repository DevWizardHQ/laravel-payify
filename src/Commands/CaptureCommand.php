<?php

namespace DevWizard\Payify\Commands;

use DevWizard\Payify\Contracts\SupportsAuthCapture;
use DevWizard\Payify\Managers\PayifyManager;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Console\Command;

class CaptureCommand extends Command
{
    protected $signature = 'payify:capture {transaction_id} {--amount=}';

    protected $description = 'Capture a previously authorized Payify transaction';

    public function handle(PayifyManager $manager): int
    {
        $txn = Transaction::find((string) $this->argument('transaction_id'));
        if (! $txn) {
            $this->error('Transaction not found.');

            return self::FAILURE;
        }

        $driver = $manager->provider($txn->provider);

        if (! $driver instanceof SupportsAuthCapture) {
            $this->error("Provider [{$txn->provider}] does not support capture.");

            return self::FAILURE;
        }

        $amount = $this->option('amount') !== null ? (float) $this->option('amount') : null;

        $response = $driver->capture($txn, $amount);

        $this->line("Transaction: {$txn->id}");
        $this->line("Status:      {$response->status->value}");
        $this->line("Amount:      {$response->amount}");

        return self::SUCCESS;
    }
}
