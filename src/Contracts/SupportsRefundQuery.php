<?php

namespace DevWizard\Payify\Contracts;

interface SupportsRefundQuery
{
    public function queryRefund(string $refundRefId): array;
}
