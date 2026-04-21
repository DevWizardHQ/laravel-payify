<?php

namespace DevWizard\Payify\Contracts;

use DevWizard\Payify\Dto\RefundRequest;
use DevWizard\Payify\Dto\RefundResponse;

interface SupportsRefund
{
    public function refund(RefundRequest $request): RefundResponse;
}
