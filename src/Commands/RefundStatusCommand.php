<?php

namespace DevWizard\Payify\Commands;

use DevWizard\Payify\Managers\PayifyManager;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Console\Command;

class RefundStatusCommand extends Command
{
    protected $signature = 'payify:refund:status {transaction_id}';

    protected $description = 'Query a stored refund reference against its provider (providers that support it)';

    public function handle(PayifyManager $manager): int
    {
        $txn = Transaction::find($this->argument('transaction_id'));
        if (! $txn) {
            $this->error('Transaction not found.');

            return self::FAILURE;
        }

        $refundRefId = data_get($txn->response_payload, 'refund.refund_ref_id');

        if (! $refundRefId) {
            $this->error('No refund_ref_id stored on this transaction.');

            return self::FAILURE;
        }

        $driver = $manager->provider($txn->provider);

        if (! method_exists($driver, 'queryRefund')) {
            $this->error("Provider [{$txn->provider}] does not support refund status queries.");

            return self::FAILURE;
        }

        $result = $driver->queryRefund($refundRefId);

        $this->line("Refund ref: {$refundRefId}");
        $this->line('Status:     '.($result['status'] ?? 'unknown'));

        return self::SUCCESS;
    }
}
