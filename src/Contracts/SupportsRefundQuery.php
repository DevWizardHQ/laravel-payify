<?php

namespace DevWizard\Payify\Contracts;

interface SupportsRefundQuery
{
    /** @return array<string, mixed> */
    public function queryRefund(string $refundRefId): array;
}
