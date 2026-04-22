<?php

namespace DevWizard\Payify\Providers\Sslcommerz;

use DevWizard\Payify\Http\PayifyHttpClient;

class SslcommerzRefundClient
{
    public function __construct(
        private PayifyHttpClient $client,
        private array $config,
    ) {}

    public function initiate(string $bankTranId, string $refundTransId, float $amount, string $remarks, ?string $refeId = null): array
    {
        return $this->client->get(
            $this->baseUrl().Constants::PATH_REFUND,
            array_filter([
                'bank_tran_id' => $bankTranId,
                'refund_trans_id' => $refundTransId,
                'refund_amount' => number_format($amount, 2, '.', ''),
                'refund_remarks' => $remarks,
                'refe_id' => $refeId,
                'store_id' => $this->credential('store_id'),
                'store_passwd' => $this->credential('store_passwd'),
                'format' => 'json',
            ], fn ($v) => $v !== null && $v !== ''),
        );
    }

    public function query(string $refundRefId): array
    {
        return $this->client->get(
            $this->baseUrl().Constants::PATH_REFUND,
            [
                'refund_ref_id' => $refundRefId,
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
