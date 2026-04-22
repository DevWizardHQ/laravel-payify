<?php

use DevWizard\Payify\Http\PayifyHttpClient;
use DevWizard\Payify\Managers\PayifyManager;
use DevWizard\Payify\Payify;

it('binds PayifyManager as singleton under the "payify" alias', function () {
    $a = app('payify');
    $b = app(PayifyManager::class);

    expect($a)->toBe($b);
});

it('binds PayifyHttpClient as singleton', function () {
    $a = app(PayifyHttpClient::class);
    $b = app(PayifyHttpClient::class);

    expect($a)->toBe($b);
});

afterEach(function () {
    Payify::resetCustomRoutes();
});

it('skips default route registration when host overrides', function () {
    config()->set('payify.routes.enabled', true);
    Payify::routes(['prefix' => 'override', 'middleware' => []]);

    $this->postJson('/override/webhook/fake', ['event' => 'payment.succeeded'])
        ->assertStatus(200);
});
