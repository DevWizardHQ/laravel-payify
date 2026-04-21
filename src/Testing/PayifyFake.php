<?php

namespace DevWizard\Payify\Testing;

class PayifyFake
{
    public static function install(array|string $providers = []): static
    {
        return new static();
    }
}
