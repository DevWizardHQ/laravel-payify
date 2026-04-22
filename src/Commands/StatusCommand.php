<?php

namespace DevWizard\Payify\Commands;

use DevWizard\Payify\Managers\PayifyManager;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;

class StatusCommand extends Command
{
    protected $signature = 'payify:status {transaction_id}';

    protected $description = 'Query provider for current transaction status and refresh the row';

    public function handle(PayifyManager $manager): int
    {
        $id = (string) $this->argument('transaction_id');

        $txn = Transaction::find($id);

        if (! $txn) {
            error("Transaction [{$id}] not found.");

            return self::FAILURE;
        }

        $before = $txn->status->value;

        $response = spin(
            fn () => $manager->provider($txn->provider)->status($txn),
            'Fetching status from provider...',
        );

        table(
            headers: ['Field', 'Value'],
            rows: [
                ['Transaction', $txn->id],
                ['Provider', $txn->provider],
                ['Before', $before],
                ['After', $response->status->value],
                ['Provider Txn', $response->providerTransactionId ?? '(none)'],
            ],
        );

        return self::SUCCESS;
    }
}
