<?php

namespace DevWizard\Payify\Providers\Bkash;

use DevWizard\Payify\Exceptions\AlreadyCompletedException;
use DevWizard\Payify\Exceptions\InvalidCredentialsException;
use DevWizard\Payify\Exceptions\PayifyException;
use DevWizard\Payify\Exceptions\PaymentFailedException;
use DevWizard\Payify\Exceptions\RefundFailedException;
use DevWizard\Payify\Exceptions\ValidationException;

class BkashErrorMap
{
    public static function map(string $code, string $message, array $raw = []): PayifyException
    {
        if ($code === Constants::ERR_INVALID_TOKEN) {
            return new InvalidCredentialsException("Invalid app token [{$code}]: {$message}");
        }

        if (in_array($code, ['2062', '2068', '2116', '2117'], true)) {
            return new AlreadyCompletedException("[{$code}] {$message}");
        }

        if (in_array($code, ['2071', '2127', '2072', '2073', '2074', '2075', '2076', '2077', '2078'], true)) {
            $e = new RefundFailedException("[{$code}] {$message}");
            $e->setProviderError($code, $message);

            return $e;
        }

        if (in_array($code, ['2065', '2048', '2049', '2063'], true)) {
            return new ValidationException("[{$code}] {$message}");
        }

        if (in_array($code, ['2080', '2081', '2082'], true)) {
            return new InvalidCredentialsException("[{$code}] {$message}");
        }

        $fallback = new PaymentFailedException($message);
        $fallback->setProviderError($code, $message);

        return $fallback;
    }
}
