<?php

use DevWizard\Payify\Drivers\FakeDriver;
use DevWizard\Payify\Exceptions\ProviderNotFoundException;
use DevWizard\Payify\Managers\PayifyManager;

beforeEach(function () {
    config()->set('payify.default', 'fake');
    config()->set('payify.providers.fake', [
        'driver' => FakeDriver::class,
        'mode' => 'sandbox',
        'credentials' => [],
    ]);
    config()->set('payify.http', [
        'timeout' => 1, 'retries' => 0, 'retry_delay' => 1,
        'mask_keys' => [], 'log_requests' => false,
    ]);
});

it('resolves a config-registered class driver', function () {
    $manager = app(PayifyManager::class);

    expect($manager->provider('fake'))->toBeInstanceOf(FakeDriver::class);
});

it('memoizes driver instances', function () {
    $manager = app(PayifyManager::class);

    expect($manager->provider('fake'))->toBe($manager->provider('fake'));
});

it('throws when provider is not configured', function () {
    $manager = app(PayifyManager::class);

    expect(fn () => $manager->provider('missing'))->toThrow(ProviderNotFoundException::class);
});

it('supports runtime extend', function () {
    $manager = app(PayifyManager::class);

    $manager->extend('inline', function ($app, $config) {
        return new FakeDriver(
            client: app(\DevWizard\Payify\Http\PayifyHttpClient::class),
            config: $config,
            events: app('events'),
            logger: logger(),
        );
    });

    config()->set('payify.providers.inline', ['mode' => 'sandbox']);

    expect($manager->provider('inline'))->toBeInstanceOf(FakeDriver::class);
});

it('reads global mode when provider has none', function () {
    config()->set('payify.providers.fake', [
        'driver' => FakeDriver::class,
        'credentials' => [],
    ]);
    config()->set('payify.mode', 'live');

    $manager = app(PayifyManager::class);

    $driver = $manager->provider('fake');
    $reflection = new ReflectionClass($driver);
    $config = $reflection->getProperty('config');
    $config->setAccessible(true);
    $resolved = $config->getValue($driver);

    expect($resolved['mode'])->toBe('live');
});
