<?php

namespace DevWizard\Payify\Events;

use DevWizard\Payify\Dto\WebhookPayload;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebhookReceived
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public WebhookPayload $payload,
        public ?Transaction $transaction,
    ) {
    }
}
