<?php

use DevWizard\Payify\Contracts\PaymentProvider;
use DevWizard\Payify\Exceptions\PayifyException;
use DevWizard\Payify\Providers\Bkash\BkashDriver;
use DevWizard\Payify\Providers\Sslcommerz\SslcommerzDriver;
use Illuminate\Database\Eloquent\Model;

arch('no debug calls')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

arch('DTOs are readonly value classes')
    ->expect('DevWizard\Payify\Dto')
    ->toBeReadonly()
    ->toBeFinal();

arch('contracts are interfaces')
    ->expect('DevWizard\Payify\Contracts')
    ->toBeInterfaces();

arch('exceptions extend PayifyException')
    ->expect('DevWizard\Payify\Exceptions')
    ->classes()
    ->toExtend(PayifyException::class)
    ->ignoring(PayifyException::class);

arch('no cross-layer coupling into Http controllers from DTOs')
    ->expect('DevWizard\Payify\Dto')
    ->not->toUse('DevWizard\Payify\Http\Controllers');

arch('bKash provider is self-contained')
    ->expect('DevWizard\Payify\Providers\Bkash')
    ->not->toUse('DevWizard\Payify\Providers\Sslcommerz');

arch('SSLCommerz provider is self-contained')
    ->expect('DevWizard\Payify\Providers\Sslcommerz')
    ->not->toUse('DevWizard\Payify\Providers\Bkash');

arch('providers implement PaymentProvider')
    ->expect([BkashDriver::class, SslcommerzDriver::class])
    ->toImplement(PaymentProvider::class);

arch('models extend Eloquent Model')
    ->expect('DevWizard\Payify\Models')
    ->toExtend(Model::class);
