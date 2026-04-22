<?php

namespace DevWizard\Payify\Commands;

use DevWizard\Payify\Contracts\SupportsAuthCapture;
use DevWizard\Payify\Managers\PayifyManager;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;

class CaptureCommand extends Command
{
    protected $signature = 'payify:capture {transaction_id} {--amount=}';

    protected $description = 'Capture a previously authorized Payify transaction';

    public function handle(PayifyManager $manager): int
    {
        $txn = Transaction::find((string) $this->argument('transaction_id'));
        if (! $txn) {
            error('Transaction not found.');

            return self::FAILURE;
        }

        $driver = $manager->provider($txn->provider);

        if (! $driver instanceof SupportsAuthCapture) {
            error("Provider [{$txn->provider}] does not support capture.");

            return self::FAILURE;
        }

        $amount = $this->option('amount') !== null ? (float) $this->option('amount') : null;

        $response = spin(fn () => $driver->capture($txn, $amount), 'Capturing transaction...');

        table(
            headers: ['Field', 'Value'],
            rows: [
                ['Transaction', $txn->id],
                ['Status', $response->status->value],
                ['Amount', (string) $response->amount],
            ],
        );

        return self::SUCCESS;
    }
}
