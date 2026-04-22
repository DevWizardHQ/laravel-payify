<?php

namespace DevWizard\Payify\Http;

use DevWizard\Payify\Exceptions\PayifyException;
use DevWizard\Payify\Http\Middleware\LoggingMiddleware;
use DevWizard\Payify\Http\Middleware\RetryMiddleware;
use DevWizard\Payify\Http\Middleware\SecretMaskingMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use Psr\Log\LoggerInterface;

class PayifyHttpClient
{
    private Client $guzzle;

    public function __construct(array $config, LoggerInterface $logger)
    {
        $masker = new SecretMaskingMiddleware($config['mask_keys'] ?? []);

        $stack = $config['handler'] ?? HandlerStack::create();
        $stack->push((new RetryMiddleware(
            (int) ($config['retries'] ?? 0),
            (int) ($config['retry_delay'] ?? 500),
        ))());
        $stack->push(new LoggingMiddleware($logger, (bool) ($config['log_requests'] ?? false), $masker));

        $this->guzzle = new Client([
            'timeout' => $config['timeout'] ?? 30,
            'connect_timeout' => $config['connect_timeout'] ?? 10,
            'verify' => $config['verify'] ?? true,
            'handler' => $stack,
            'http_errors' => false,
            'headers' => [
                'User-Agent' => $config['user_agent'] ?? 'Payify/1.0',
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function post(string $url, array $data = [], array $headers = []): array
    {
        return $this->request('POST', $url, [$this->bodyKey($headers) => $data, 'headers' => $headers]);
    }

    /**
     * POST with application/x-www-form-urlencoded body. Explicit alternative for
     * providers that reject JSON (e.g. SSLCommerz v4 /gwprocess).
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     */
    public function postForm(string $url, array $data = [], array $headers = []): array
    {
        return $this->request('POST', $url, ['form_params' => $data, 'headers' => $headers]);
    }

    public function get(string $url, array $query = [], array $headers = []): array
    {
        return $this->request('GET', $url, ['query' => $query, 'headers' => $headers]);
    }

    public function put(string $url, array $data = [], array $headers = []): array
    {
        return $this->request('PUT', $url, [$this->bodyKey($headers) => $data, 'headers' => $headers]);
    }

    public function delete(string $url, array $headers = []): array
    {
        return $this->request('DELETE', $url, ['headers' => $headers]);
    }

    /** @param  array<string, string>  $headers */
    private function bodyKey(array $headers): string
    {
        foreach ($headers as $name => $value) {
            if (strcasecmp((string) $name, 'Content-Type') === 0 && str_contains(strtolower((string) $value), 'x-www-form-urlencoded')) {
                return 'form_params';
            }
        }

        return 'json';
    }

    public function raw(): Client
    {
        return $this->guzzle;
    }

    private function request(string $method, string $url, array $options): array
    {
        try {
            $response = $this->guzzle->request($method, $url, $options);
        } catch (ConnectException $e) {
            throw new class('HTTP connection failed: '.$e->getMessage(), 0, $e) extends PayifyException {};
        } catch (GuzzleException $e) {
            throw new class('HTTP error: '.$e->getMessage(), 0, $e) extends PayifyException {};
        }

        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : ['_raw' => $body];
    }
}
