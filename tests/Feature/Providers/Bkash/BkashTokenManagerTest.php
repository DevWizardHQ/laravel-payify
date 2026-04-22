<?php

use DevWizard\Payify\Http\PayifyHttpClient;
use DevWizard\Payify\Providers\Bkash\BkashTokenManager;
use DevWizard\Payify\Tests\Fixtures\FixtureLoader;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

function makeTokenManager(MockHandler $mock, string $mode = 'sandbox'): BkashTokenManager
{
    $config = [
        'mode' => $mode,
        'credentials' => [
            'app_key' => 'app_key_x', 'app_secret' => 'app_secret_x',
            'username' => 'user', 'password' => 'pass',
        ],
        'sandbox_url' => 'https://tokenized.sandbox.bka.sh/v1.2.0-beta',
        'live_url' => 'https://tokenized.pay.bka.sh/v1.2.0-beta',
        'cache_store' => null,
        'token_safety_margin' => 60,
    ];

    $client = new PayifyHttpClient([
        'timeout' => 5, 'retries' => 0, 'retry_delay' => 1,
        'mask_keys' => [], 'handler' => HandlerStack::create($mock),
    ], Log::getLogger());

    return new BkashTokenManager($client, $config);
}

it('grants a token and caches it', function () {
    $mock = new MockHandler([FixtureLoader::json('Bkash/grant-token-success.json')]);
    $manager = makeTokenManager($mock);

    $token = $manager->idToken();

    expect($token)->toBe('ey.test.idtoken');
    expect(Cache::has('payify:bkash:sandbox:id_token'))->toBeTrue();
});

it('returns cached token on subsequent calls', function () {
    $mock = new MockHandler([
        FixtureLoader::json('Bkash/grant-token-success.json'),
        FixtureLoader::json('Bkash/grant-token-invalid.json'),
    ]);
    $manager = makeTokenManager($mock);

    $t1 = $manager->idToken();
    $t2 = $manager->idToken();

    expect($t1)->toBe($t2);
    expect($mock->count())->toBe(1);
});

it('forget() clears cache and forces re-grant', function () {
    $mock = new MockHandler([
        FixtureLoader::json('Bkash/grant-token-success.json'),
        FixtureLoader::json('Bkash/grant-token-success.json'),
    ]);
    $manager = makeTokenManager($mock);

    $manager->idToken();
    $manager->forget();
    $manager->idToken();

    expect($mock->count())->toBe(0);
});

it('scopes cache key by mode', function () {
    $mock = new MockHandler([FixtureLoader::json('Bkash/grant-token-success.json')]);
    $manager = makeTokenManager($mock, mode: 'live');

    $manager->idToken();

    expect(Cache::has('payify:bkash:live:id_token'))->toBeTrue();
    expect(Cache::has('payify:bkash:sandbox:id_token'))->toBeFalse();
});
