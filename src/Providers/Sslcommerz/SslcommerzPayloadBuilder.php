<?php

namespace DevWizard\Payify\Providers\Sslcommerz;

use DevWizard\Payify\Dto\LineItem;
use DevWizard\Payify\Dto\PaymentRequest;

class SslcommerzPayloadBuilder
{
    public function __construct(private array $config) {}

    public function build(PaymentRequest $req, ?string $ipnUrl = null): array
    {
        $customer = $req->customer;
        $defaults = $this->config['defaults'] ?? [];

        $payload = [
            'store_id' => $this->config['credentials']['store_id'] ?? '',
            'store_passwd' => $this->config['credentials']['store_passwd'] ?? '',
            'total_amount' => number_format($req->amount, 2, '.', ''),
            'currency' => $req->currency,
            'tran_id' => $req->reference,
            'product_category' => $req->productCategory ?? $defaults['product_category'] ?? 'General',
            'product_name' => $req->productName ?? $defaults['product_name'] ?? 'Payment',
            'product_profile' => $req->productProfile ?? $defaults['product_profile'] ?? 'general',
            'success_url' => $this->resolveCallbackUrl($req, 'success'),
            'fail_url' => $this->resolveCallbackUrl($req, 'fail'),
            'cancel_url' => $this->resolveCallbackUrl($req, 'cancel'),
            'cus_name' => $customer?->name ?? '',
            'cus_email' => $customer?->email ?? '',
            'cus_phone' => $customer?->phone ?? '',
            'cus_add1' => $customer?->address1 ?? '',
            'cus_add2' => $customer?->address2 ?? '',
            'cus_city' => $customer?->city ?? '',
            'cus_state' => $customer?->state ?? '',
            'cus_postcode' => $customer?->postcode ?? '',
            'cus_country' => $customer?->country ?? 'Bangladesh',
        ];

        if ($ipnUrl ?? $req->webhookUrl) {
            $payload['ipn_url'] = (string) ($ipnUrl ?? $req->webhookUrl);
        }

        if ($req->lineItems !== []) {
            $payload['cart'] = json_encode(array_map(
                fn (LineItem $item) => [
                    'product' => $item->name,
                    'amount' => number_format($item->total(), 2, '.', ''),
                ],
                $req->lineItems,
            ));
        }

        if ($req->emiOption !== null) {
            $payload['emi_option'] = (string) $req->emiOption;
            if ($req->emiMaxInstallments !== null) {
                $payload['emi_max_inst_option'] = $req->emiMaxInstallments;
            }
        }

        foreach (['value_a', 'value_b', 'value_c', 'value_d'] as $key) {
            if (isset($req->extras[$key])) {
                $payload[$key] = (string) $req->extras[$key];
            }
        }

        return $payload;
    }

    private function resolveCallbackUrl(PaymentRequest $req, string $kind): string
    {
        $base = (string) ($req->callbackUrl ?? '');
        if ($base === '') {
            return '';
        }

        if (! str_contains($base, $kind)) {
            $separator = str_contains($base, '?') ? '&' : '?';

            return $base.$separator.'status='.$kind;
        }

        return $base;
    }
}
