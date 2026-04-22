<?php

namespace DevWizard\Payify\Providers\Sslcommerz;

use DevWizard\Payify\Dto\PaymentRequest;
use DevWizard\Payify\Http\PayifyHttpClient;

class SslcommerzGateway
{
    public function __construct(
        private PayifyHttpClient $client,
        private array $config,
        private SslcommerzPayloadBuilder $payloadBuilder,
    ) {}

    public function initSession(PaymentRequest $req, ?string $ipnUrl = null): array
    {
        $payload = $this->payloadBuilder->build($req, $ipnUrl);

        $response = $this->client->postForm($this->baseUrl().Constants::PATH_INIT, $payload);

        if (($response['status'] ?? '') !== 'SUCCESS') {
            $reason = (string) ($response['failedreason'] ?? 'Init failed');
            throw SslcommerzErrorMap::map('FAILED', $reason, $response);
        }

        $response['redirectUrl'] = $this->resolveRedirectUrl($response, $req->gateway);

        return $response;
    }

    private function resolveRedirectUrl(array $response, ?string $gateway): string
    {
        if ($gateway && isset($response['desc']) && is_array($response['desc'])) {
            foreach ($response['desc'] as $method) {
                if (($method['gw'] ?? null) === $gateway && ! empty($method['redirectGatewayURL'])) {
                    return (string) $method['redirectGatewayURL'];
                }
            }
        }

        return (string) ($response['GatewayPageURL'] ?? '');
    }

    private function baseUrl(): string
    {
        $mode = $this->config['mode'] ?? 'sandbox';

        return (string) $this->config[$mode === 'live' ? 'live_url' : 'sandbox_url'];
    }
}
