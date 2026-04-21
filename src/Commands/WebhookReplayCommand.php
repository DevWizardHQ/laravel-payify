<?php

namespace DevWizard\Payify\Commands;

use DevWizard\Payify\Dto\WebhookPayload;
use DevWizard\Payify\Events\WebhookReceived;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Console\Command;

class WebhookReplayCommand extends Command
{
    protected $signature = 'payify:webhook:replay {transaction_id}';

    protected $description = 'Re-fire a stored webhook payload as a WebhookReceived event';

    public function handle(): int
    {
        $txn = Transaction::find($this->argument('transaction_id'));

        if (! $txn) {
            $this->error('Transaction not found.');
            return self::FAILURE;
        }

        if (empty($txn->webhook_payload)) {
            $this->error('No stored webhook payload for this transaction.');
            return self::FAILURE;
        }

        $raw = $txn->webhook_payload;

        $payload = new WebhookPayload(
            provider: $txn->provider,
            event: $raw['event'] ?? 'unknown',
            providerTransactionId: $raw['provider_transaction_id'] ?? $txn->provider_transaction_id,
            reference: $raw['reference'] ?? $txn->reference,
            amount: isset($raw['amount']) ? (float) $raw['amount'] : (float) $txn->amount,
            currency: $raw['currency'] ?? $txn->currency,
            raw: $raw,
            verified: $txn->webhook_verified_at !== null,
        );

        event(new WebhookReceived($payload, $txn));
        $this->info("Replayed webhook for transaction {$txn->id}.");

        return self::SUCCESS;
    }
}
