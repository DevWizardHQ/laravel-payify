<?php

namespace DevWizard\Payify\Providers\Bkash;

use DevWizard\Payify\Exceptions\InvalidCredentialsException;
use DevWizard\Payify\Http\PayifyHttpClient;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;

class BkashTokenManager
{
    public function __construct(
        private PayifyHttpClient $client,
        private array $config,
    ) {}

    public function idToken(): string
    {
        $cached = $this->cache()->get($this->key('id_token'));
        if ($cached) {
            return $cached;
        }

        return $this->grant();
    }

    public function refreshToken(): ?string
    {
        return $this->cache()->get($this->key('refresh_token'));
    }

    public function forget(): void
    {
        $this->cache()->forget($this->key('id_token'));
        $this->cache()->forget($this->key('refresh_token'));
    }

    private function grant(): string
    {
        $response = $this->client->post(
            $this->baseUrl().Constants::PATH_GRANT,
            [
                'app_key' => $this->config['credentials']['app_key'],
                'app_secret' => $this->config['credentials']['app_secret'],
            ],
            [
                'username' => $this->config['credentials']['username'],
                'password' => $this->config['credentials']['password'],
                'Content-Type' => 'application/json',
            ],
        );

        if (($response['statusCode'] ?? '') !== Constants::STATUS_SUCCESS || empty($response['id_token'])) {
            $code = $response['statusCode'] ?? 'UNKNOWN';
            $message = $response['statusMessage'] ?? 'Grant failed';
            throw new InvalidCredentialsException("bKash token grant failed [{$code}]: {$message}");
        }

        $ttl = max(60, (int) ($response['expires_in'] ?? 3600) - (int) ($this->config['token_safety_margin'] ?? 60));

        $this->cache()->put($this->key('id_token'), $response['id_token'], $ttl);
        if (! empty($response['refresh_token'])) {
            $this->cache()->put($this->key('refresh_token'), $response['refresh_token'], $ttl * 2);
        }

        return $response['id_token'];
    }

    private function cache(): CacheRepository
    {
        return Cache::store($this->config['cache_store'] ?? config('cache.default'));
    }

    private function key(string $suffix): string
    {
        $mode = $this->config['mode'] ?? 'sandbox';

        return "payify:bkash:{$mode}:{$suffix}";
    }

    private function baseUrl(): string
    {
        $mode = $this->config['mode'] ?? 'sandbox';

        return (string) $this->config[$mode === 'live' ? 'live_url' : 'sandbox_url'];
    }
}
