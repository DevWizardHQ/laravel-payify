<?php

namespace DevWizard\Payify\Providers\Sslcommerz;

use DevWizard\Payify\Exceptions\InvalidCredentialsException;
use DevWizard\Payify\Exceptions\PayifyException;
use DevWizard\Payify\Exceptions\PaymentFailedException;
use DevWizard\Payify\Exceptions\ValidationException;

class SslcommerzErrorMap
{
    public static function map(string $status, string $message, array $raw = []): PayifyException
    {
        if ($status === 'FAILED' || $status === 'INVALID_TRANSACTION') {
            $e = new PaymentFailedException($message);
            $e->setProviderError($status, $message);

            return $e;
        }

        if ($status === 'INACTIVE') {
            return new InvalidCredentialsException("SSLCommerz rejected request (IP not whitelisted?): {$message}");
        }

        if ($status === 'INVALID_REQUEST') {
            return new ValidationException("[{$status}] {$message}");
        }

        $fallback = new PaymentFailedException($message);
        $fallback->setProviderError($status, $message);

        return $fallback;
    }
}
