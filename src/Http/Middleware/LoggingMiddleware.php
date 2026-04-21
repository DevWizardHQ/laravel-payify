<?php

namespace DevWizard\Payify\Http\Middleware;

use GuzzleHttp\Promise\Create;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class LoggingMiddleware
{
    public function __construct(
        private LoggerInterface $logger,
        private bool $logBodies,
        private SecretMaskingMiddleware $masker,
    ) {}

    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $start = microtime(true);

            return $handler($request, $options)->then(
                function (ResponseInterface $response) use ($request, $start) {
                    $this->log('payify.http', $request, $response, $start);

                    return $response;
                },
                function ($reason) use ($request, $start) {
                    $this->log('payify.http.error', $request, null, $start, $reason);

                    return Create::rejectionFor($reason);
                }
            );
        };
    }

    private function log(string $channel, RequestInterface $req, ?ResponseInterface $res, float $start, mixed $error = null): void
    {
        $context = [
            'method' => $req->getMethod(),
            'url' => (string) $req->getUri(),
            'status' => $res?->getStatusCode(),
            'duration_ms' => (int) ((microtime(true) - $start) * 1000),
            'headers' => $this->masker->maskHeaders($this->flattenHeaders($req->getHeaders())),
        ];

        if ($this->logBodies) {
            $context['request_body'] = $this->decodeMasked((string) $req->getBody());
            if ($res) {
                $context['response_body'] = $this->decodeMasked((string) $res->getBody());
                $res->getBody()->rewind();
            }
            $req->getBody()->rewind();
        }

        if ($error) {
            $context['error'] = $error instanceof \Throwable ? $error->getMessage() : (string) $error;
            $this->logger->warning($channel, $context);
        } else {
            $this->logger->info($channel, $context);
        }
    }

    private function flattenHeaders(array $headers): array
    {
        return array_map(fn ($v) => is_array($v) ? implode(', ', $v) : $v, $headers);
    }

    private function decodeMasked(string $body): mixed
    {
        $decoded = json_decode($body, true);

        return is_array($decoded) ? $this->masker->maskPayload($decoded) : $body;
    }
}
