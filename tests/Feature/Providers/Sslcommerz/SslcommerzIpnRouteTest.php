<?php

use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Events\PaymentSucceeded;
use DevWizard\Payify\Events\WebhookReceived;
use DevWizard\Payify\Models\Transaction;
use DevWizard\Payify\Providers\Sslcommerz\SslcommerzDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('payify.default', 'sslcommerz');
    config()->set('payify.providers.sslcommerz', [
        'driver' => SslcommerzDriver::class,
        'mode' => 'sandbox',
        'credentials' => ['store_id' => 'testbox', 'store_passwd' => 'qwerty'],
        'sandbox_url' => 'https://sandbox.sslcommerz.com',
        'live_url' => 'https://securepay.sslcommerz.com',
        'security' => [
            'verify_ip' => false,
            'verify_signature' => false,
            'verify_validator' => false,
        ],
        'defaults' => [],
        'embed' => [
            'sandbox_script' => 'https://sandbox.sslcommerz.com/embed.min.js?0.0.1',
            'live_script' => 'https://seamless-epay.sslcommerz.com/embed.min.js?0.0.1',
        ],
    ]);
    config()->set('payify.routes.middleware', []);
});

it('accepts IPN, verifies, marks transaction succeeded, fires events', function () {
    Event::fake([PaymentSucceeded::class, WebhookReceived::class]);

    $txn = Transaction::create([
        'provider' => 'sslcommerz', 'reference' => 'INV-SSL-IPN',
        'amount' => 1000, 'currency' => 'BDT', 'status' => TransactionStatus::Processing,
    ]);

    $response = $this->postJson('/payify/webhook/sslcommerz', [
        'tran_id' => 'INV-SSL-IPN',
        'bank_tran_id' => 'BT-123',
        'val_id' => 'VAL-IPN',
        'amount' => '1000.00',
        'currency' => 'BDT',
        'status' => 'VALID',
    ]);

    $response->assertOk();
    expect($txn->fresh()->status)->toBe(TransactionStatus::Succeeded);
    expect($txn->fresh()->webhook_verified_at)->not->toBeNull();
    Event::assertDispatched(PaymentSucceeded::class);
    Event::assertDispatched(WebhookReceived::class);
});
