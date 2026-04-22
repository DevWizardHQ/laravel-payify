<?php

use DevWizard\Payify\Dto\WebhookPayload;
use DevWizard\Payify\Exceptions\IpNotAllowedException;
use DevWizard\Payify\Exceptions\WebhookVerificationException;
use DevWizard\Payify\Http\PayifyHttpClient;
use DevWizard\Payify\Providers\Sslcommerz\SslcommerzIpnVerifier;
use DevWizard\Payify\Providers\Sslcommerz\SslcommerzValidator;
use DevWizard\Payify\Tests\Fixtures\FixtureLoader;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

function sslIpnVerifier(array $security, ?MockHandler $validatorMock = null): SslcommerzIpnVerifier
{
    $client = new PayifyHttpClient([
        'timeout' => 5, 'retries' => 0, 'retry_delay' => 1,
        'mask_keys' => [], 'handler' => HandlerStack::create($validatorMock ?? new MockHandler([])),
    ], Log::getLogger());

    $config = [
        'mode' => 'sandbox',
        'sandbox_url' => 'https://sandbox.sslcommerz.com',
        'live_url' => 'https://securepay.sslcommerz.com',
        'credentials' => ['store_id' => 'testbox', 'store_passwd' => 'qwerty'],
        'security' => $security,
    ];

    return new SslcommerzIpnVerifier($config, new SslcommerzValidator($client, $config));
}

it('rejects when source IP is not in allowlist', function () {
    $verifier = sslIpnVerifier([
        'verify_ip' => true,
        'allowed_ips_sandbox' => ['1.1.1.1'],
        'verify_signature' => false,
        'verify_validator' => false,
    ]);

    $request = Request::create('/w', 'POST', ['tran_id' => 'T']);
    $request->server->set('REMOTE_ADDR', '9.9.9.9');

    expect(fn () => $verifier->verify($request))->toThrow(IpNotAllowedException::class);
});

it('rejects on signature mismatch', function () {
    $verifier = sslIpnVerifier([
        'verify_ip' => false,
        'verify_signature' => true,
        'verify_validator' => false,
    ]);

    $request = Request::create('/w', 'POST', [
        'tran_id' => 'T', 'amount' => '100.00',
        'verify_key' => 'tran_id,amount',
        'verify_sign' => 'WRONG-SIG',
    ]);

    expect(fn () => $verifier->verify($request))->toThrow(WebhookVerificationException::class);
});

it('accepts verified signed payload', function () {
    $verifier = sslIpnVerifier([
        'verify_ip' => false,
        'verify_signature' => true,
        'verify_validator' => false,
    ]);

    $tranId = 'T-SIGN';
    $amount = '100.00';
    $storePasswd = 'qwerty';
    $concat = $tranId.'|'.$amount;
    $sign = md5($concat.md5($storePasswd));

    $request = Request::create('/w', 'POST', [
        'tran_id' => $tranId, 'amount' => $amount, 'status' => 'VALID',
        'verify_key' => 'tran_id,amount',
        'verify_sign' => $sign,
    ]);

    $payload = $verifier->verify($request);

    expect($payload)->toBeInstanceOf(WebhookPayload::class);
    expect($payload->verified)->toBeTrue();
    expect($payload->event)->toBe('payment.succeeded');
});

it('validator layer rejects on disagreement', function () {
    $mock = new MockHandler([
        FixtureLoader::json('Sslcommerz/validator-valid.json'),
    ]);
    $verifier = sslIpnVerifier([
        'verify_ip' => false,
        'verify_signature' => false,
        'verify_validator' => true,
    ], $mock);

    $request = Request::create('/w', 'POST', [
        'tran_id' => 'INV-SSL-1', 'amount' => '500.00',
        'val_id' => 'VAL123', 'status' => 'VALID',
    ]);

    expect(fn () => $verifier->verify($request))->toThrow(WebhookVerificationException::class);
});
