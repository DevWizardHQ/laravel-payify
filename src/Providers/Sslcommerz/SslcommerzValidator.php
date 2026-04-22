<?php

namespace DevWizard\Payify\Providers\Sslcommerz;

use DevWizard\Payify\Http\PayifyHttpClient;

class SslcommerzValidator
{
    public function __construct(
        private PayifyHttpClient $client,
        private array $config,
    ) {}

    public function validateByValId(string $valId): array
    {
        return $this->client->get(
            $this->baseUrl().Constants::PATH_VALIDATOR,
            [
                'val_id' => $valId,
                'store_id' => $this->credential('store_id'),
                'store_passwd' => $this->credential('store_passwd'),
                'format' => 'json',
                'v' => '1',
            ],
        );
    }

    public function queryByTranId(string $tranId): array
    {
        return $this->client->get(
            $this->baseUrl().Constants::PATH_TRANSACTION,
            [
                'tran_id' => $tranId,
                'store_id' => $this->credential('store_id'),
                'store_passwd' => $this->credential('store_passwd'),
                'format' => 'json',
            ],
        );
    }

    public function queryBySessionKey(string $sessionKey): array
    {
        return $this->client->get(
            $this->baseUrl().Constants::PATH_TRANSACTION,
            [
                'sessionkey' => $sessionKey,
                'store_id' => $this->credential('store_id'),
                'store_passwd' => $this->credential('store_passwd'),
                'format' => 'json',
            ],
        );
    }

    private function baseUrl(): string
    {
        $mode = $this->config['mode'] ?? 'sandbox';

        return (string) $this->config[$mode === 'live' ? 'live_url' : 'sandbox_url'];
    }

    private function credential(string $key): string
    {
        return (string) ($this->config['credentials'][$key] ?? '');
    }
}
