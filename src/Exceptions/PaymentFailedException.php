<?php

namespace DevWizard\Payify\Exceptions;

class PaymentFailedException extends PayifyException
{
    private ?string $providerCode = null;

    private ?string $providerMessage = null;

    public function setProviderError(?string $code, ?string $message): self
    {
        $this->providerCode = $code;
        $this->providerMessage = $message;

        return $this;
    }

    public function providerErrorCode(): ?string
    {
        return $this->providerCode;
    }

    public function providerErrorMessage(): ?string
    {
        return $this->providerMessage;
    }
}
