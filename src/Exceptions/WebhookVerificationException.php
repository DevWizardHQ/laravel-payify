<?php

namespace DevWizard\Payify\Exceptions;

class WebhookVerificationException extends PayifyException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        private ?string $reason = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function reason(): ?string
    {
        return $this->reason;
    }
}
