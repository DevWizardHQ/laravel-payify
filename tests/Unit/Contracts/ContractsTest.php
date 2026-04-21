<?php

use DevWizard\Payify\Contracts\HandlesWebhook;
use DevWizard\Payify\Contracts\PaymentProvider;
use DevWizard\Payify\Contracts\SupportsDirectApi;
use DevWizard\Payify\Contracts\SupportsHostedCheckout;
use DevWizard\Payify\Contracts\SupportsRefund;
use DevWizard\Payify\Contracts\SupportsTokenization;

it('exposes all contracts as interfaces', function () {
    foreach ([
        PaymentProvider::class,
        SupportsRefund::class,
        SupportsTokenization::class,
        SupportsHostedCheckout::class,
        SupportsDirectApi::class,
        HandlesWebhook::class,
    ] as $contract) {
        expect(interface_exists($contract))->toBeTrue("Missing: $contract");
    }
});
