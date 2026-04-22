<?php

namespace DevWizard\Payify\Jobs;

use DevWizard\Payify\Dto\WebhookPayload;
use DevWizard\Payify\Events\WebhookReceived;
use DevWizard\Payify\Http\Controllers\WebhookController;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public WebhookPayload $payload,
        public ?string $transactionId,
    ) {
        $this->tries = (int) config('payify.webhooks.tries', 3);
        $this->backoff = (int) config('payify.webhooks.backoff', 10);
    }

    public function handle(): void
    {
        $txn = $this->transactionId ? Transaction::find($this->transactionId) : null;

        if ($txn) {
            WebhookController::applyStatusTransition($txn, $this->payload);
            $txn->refresh();
        }

        event(new WebhookReceived($this->payload, $txn));
    }
}
