<?php

use DevWizard\Payify\Http\Middleware\RetryMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

function retryingClient(MockHandler $mock, int $maxRetries = 2): Client
{
    $stack = HandlerStack::create($mock);
    $stack->push((new RetryMiddleware($maxRetries, 0))());

    return new Client(['handler' => $stack]);
}

it('retries a server error and returns the later success', function () {
    $mock = new MockHandler([new Response(503), new Response(200)]);

    expect(retryingClient($mock)->get('https://example.test')->getStatusCode())->toBe(200);
    expect($mock->count())->toBe(0);
});

it('does not retry a client error', function () {
    $mock = new MockHandler([new Response(422), new Response(200)]);

    expect(fn () => retryingClient($mock)->get('https://example.test'))->toThrow(ClientException::class);
    expect($mock->count())->toBe(1);
});

it('stops after the retry limit', function () {
    $mock = new MockHandler([new Response(500), new Response(500), new Response(500), new Response(200)]);

    expect(fn () => retryingClient($mock, 2)->get('https://example.test'))->toThrow(ServerException::class);
    expect($mock->count())->toBe(1);
});
