<?php

namespace DevWizard\Payify\Commands;

use DevWizard\Payify\Managers\PayifyManager;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Console\Command;

class StatusCommand extends Command
{
    protected $signature = 'payify:status {transaction_id}';

    protected $description = 'Query provider for current transaction status and refresh the row';

    public function handle(PayifyManager $manager): int
    {
        $id = $this->argument('transaction_id');

        $txn = Transaction::find($id);

        if (! $txn) {
            $this->error("Transaction [{$id}] not found.");

            return self::FAILURE;
        }

        $before = $txn->status->value;

        $response = $manager->provider($txn->provider)->status($txn);

        $this->line("Transaction: {$txn->id}");
        $this->line("  Provider: {$txn->provider}");
        $this->line("  Before:   {$before}");
        $this->line("  After:    {$response->status->value}");
        $this->line('  Provider txn: '.($response->providerTransactionId ?? '(none)'));

        return self::SUCCESS;
    }
}
