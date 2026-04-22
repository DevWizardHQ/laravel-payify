<?php

namespace DevWizard\Payify\Commands;

use DevWizard\Payify\Contracts\SupportsRefund;
use DevWizard\Payify\Dto\RefundRequest;
use DevWizard\Payify\Managers\PayifyManager;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;

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
            error('Transaction not found.');

            return self::FAILURE;
        }

        $driver = $manager->provider($txn->provider);

        if (! $driver instanceof SupportsRefund) {
            error("Provider [{$txn->provider}] does not support refunds.");

            return self::FAILURE;
        }

        if (! $txn->canRefund()) {
            error("Transaction [{$txn->id}] is not in a refundable state (status: {$txn->status->value}).");

            return self::FAILURE;
        }

        $amount = $this->option('amount') !== null ? (float) $this->option('amount') : null;
        $label = $amount !== null ? "Refund {$txn->currency} {$amount} for transaction {$txn->id}?" : "Refund full amount for transaction {$txn->id}?";

        if (! confirm($label, default: false)) {
            return self::FAILURE;
        }

        $response = spin(
            fn () => $driver->refund(new RefundRequest(
                transactionId: $txn->id,
                amount: $amount,
                reason: $this->option('reason'),
            )),
            'Processing refund...',
        );

        table(
            headers: ['Field', 'Value'],
            rows: [
                ['Refund ID', $response->refundId],
                ['Amount', (string) $response->amount],
                ['Status', $response->status->value],
            ],
        );

        return self::SUCCESS;
    }
}
