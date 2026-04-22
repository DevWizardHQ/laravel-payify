<?php

namespace DevWizard\Payify\Contracts;

use DevWizard\Payify\Dto\PayoutRequest;
use DevWizard\Payify\Dto\PayoutResponse;

interface SupportsPayout
{
    public function initPayout(PayoutRequest $request): PayoutResponse;

    public function executePayout(string $payoutId, PayoutRequest $request): PayoutResponse;

    public function payout(PayoutRequest $request): PayoutResponse;
}
