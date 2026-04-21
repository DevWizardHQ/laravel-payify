<?php

use DevWizard\Payify\Http\PayifyHttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

function buildClient(MockHandler $mock, array $overrides = []): PayifyHttpClient
{
    $config = array_merge([
        'timeout' => 5,
        'connect_timeout' => 2,
        'retries' => 0,
        'retry_delay' => 10,
        'verify' => false,
        'user_agent' => 'Payify-Test',
        'log_requests' => false,
        'mask_keys' => ['secret'],
        'handler' => HandlerStack::create($mock),
    ], $overrides);

    return new PayifyHttpClient($config, Log::getLogger());
}

it('posts JSON and returns decoded array', function () {
    $mock = new MockHandler([
        new Response(200, ['Content-Type' => 'application/json'], json_encode(['ok' => true])),
    ]);

    $client = buildClient($mock);
    $result = $client->post('https://test.example/pay', ['amount' => 10]);

    expect($result)->toBe(['ok' => true]);
});

it('returns decoded body even on 4xx (no exception)', function () {
    $mock = new MockHandler([
        new Response(400, [], json_encode(['error' => 'bad'])),
    ]);

    $client = buildClient($mock);
    expect($client->post('https://test.example/fail', []))->toBe(['error' => 'bad']);
});

it('returns raw body string when response is not JSON', function () {
    $mock = new MockHandler([
        new Response(200, ['Content-Type' => 'text/html'], '<html></html>'),
    ]);

    $client = buildClient($mock);
    expect($client->post('https://test.example/html', []))->toBe(['_raw' => '<html></html>']);
});

it('exposes get/put/delete', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode(['method' => 'GET'])),
        new Response(200, [], json_encode(['method' => 'PUT'])),
        new Response(204, [], ''),
    ]);

    $client = buildClient($mock);

    expect($client->get('https://x')['method'])->toBe('GET');
    expect($client->put('https://x')['method'])->toBe('PUT');
    expect($client->delete('https://x'))->toBe(['_raw' => '']);
});
