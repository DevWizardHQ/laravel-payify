<?php

namespace DevWizard\Payify\Commands;

use DevWizard\Payify\Contracts\SupportsRefund;
use DevWizard\Payify\Dto\RefundRequest;
use DevWizard\Payify\Managers\PayifyManager;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Console\Command;

class RefundCommand extends Command
{
    protected $signature = 'payify:refund
        {transaction_id}
        {--amount= : Partial refund amount (defaults to full)}
        {--reason= : Human-readable reason}';

    protected $description = 'Refund a Payify transaction via its provider';

    public function handle(PayifyManager $manager): int
    {
        $txn = Transaction::find((string) $this->argument('transaction_id'));

        if (! $txn) {
            $this->error('Transaction not found.');

            return self::FAILURE;
        }

        $driver = $manager->provider($txn->provider);

        if (! $driver instanceof SupportsRefund) {
            $this->error("Provider [{$txn->provider}] does not support refunds.");

            return self::FAILURE;
        }

        if (! $txn->canRefund()) {
            $this->error("Transaction [{$txn->id}] is not in a refundable state (status: {$txn->status->value}).");

            return self::FAILURE;
        }

        $amount = $this->option('amount') !== null ? (float) $this->option('amount') : null;

        $response = $driver->refund(new RefundRequest(
            transactionId: $txn->id,
            amount: $amount,
            reason: $this->option('reason'),
        ));

        $this->line("Refund ID:  {$response->refundId}");
        $this->line("Amount:     {$response->amount}");
        $this->line("Status:     {$response->status->value}");

        return self::SUCCESS;
    }
}
