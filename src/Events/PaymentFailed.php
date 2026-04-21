<?php

namespace DevWizard\Payify\Events;

use DevWizard\Payify\Models\Transaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentFailed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Transaction $transaction,
        public string $errorCode,
        public string $errorMessage,
    ) {}
}
