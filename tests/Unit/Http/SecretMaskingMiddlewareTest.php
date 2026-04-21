<?php

use DevWizard\Payify\Http\Middleware\SecretMaskingMiddleware;

it('masks matching JSON keys recursively', function () {
    $masker = new SecretMaskingMiddleware(['secret', 'password']);

    $result = $masker->maskPayload([
        'user' => 'iqbal',
        'app_secret' => 'shh',
        'nested' => [
            'password' => 'p1',
            'safe' => 'ok',
        ],
    ]);

    expect($result['user'])->toBe('iqbal');
    expect($result['app_secret'])->toBe('***');
    expect($result['nested']['password'])->toBe('***');
    expect($result['nested']['safe'])->toBe('ok');
});

it('masks matching headers case-insensitively', function () {
    $masker = new SecretMaskingMiddleware(['authorization', 'api_key']);

    $result = $masker->maskHeaders([
        'Authorization' => 'Bearer x',
        'X-Api-Key' => 'k',
        'Accept' => 'application/json',
    ]);

    expect($result['Authorization'])->toBe('***');
    expect($result['X-Api-Key'])->toBe('***');
    expect($result['Accept'])->toBe('application/json');
});

it('leaves payload untouched when no keys configured', function () {
    $masker = new SecretMaskingMiddleware([]);
    $data = ['password' => 'visible'];
    expect($masker->maskPayload($data))->toBe($data);
});
