<?php

namespace DevWizard\Payify\Events;

use DevWizard\Payify\Dto\RefundResponse;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentRefunded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Transaction $transaction,
        public RefundResponse $refund,
    ) {
    }
}
