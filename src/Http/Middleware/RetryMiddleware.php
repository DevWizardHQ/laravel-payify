<?php

namespace DevWizard\Payify\Http\Middleware;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RetryMiddleware
{
    public function __construct(
        private int $maxRetries,
        private int $baseDelayMs,
    ) {
    }

    public function __invoke(): callable
    {
        return Middleware::retry(
            fn (int $retries, RequestInterface $req, ?ResponseInterface $res, ?\Throwable $err) => $this->shouldRetry($retries, $res, $err),
            fn (int $retries) => (int) ($this->baseDelayMs * (2 ** ($retries - 1))),
        );
    }

    private function shouldRetry(int $retries, ?ResponseInterface $res, ?\Throwable $err): bool
    {
        if ($retries >= $this->maxRetries) {
            return false;
        }

        if ($err instanceof ConnectException) {
            return true;
        }

        if ($res && $res->getStatusCode() >= 500) {
            return true;
        }

        if ($err instanceof RequestException && $err->hasResponse() && $err->getResponse()->getStatusCode() >= 500) {
            return true;
        }

        return false;
    }
}
