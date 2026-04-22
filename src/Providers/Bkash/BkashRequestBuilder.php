<?php

namespace DevWizard\Payify\Providers\Bkash;

use DevWizard\Payify\Dto\PaymentRequest;

class BkashRequestBuilder
{
    public function sanitize(string $input): string
    {
        $clean = str_replace(['<', '>', '&'], '', $input);

        return mb_substr($clean, 0, 255);
    }

    public function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    /** @param  array<string, string>  $fields */
    public function tlv(array $fields): string
    {
        $out = '';
        foreach ($fields as $tag => $value) {
            $out .= $tag.str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT).$value;
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildCreate(PaymentRequest $req, ?string $agreementId = null, string $defaultMode = Constants::MODE_CHECKOUT, string $defaultIntent = Constants::INTENT_SALE): array
    {
        $mode = $agreementId ? Constants::MODE_AGREEMENT_PAY : ($req->mode ?? $defaultMode);
        $intent = $req->intent ?? $defaultIntent;

        $payload = [
            'mode' => $mode,
            'payerReference' => $this->sanitize((string) ($req->customer?->phone ?? '')),
            'callbackURL' => (string) $req->callbackUrl,
            'amount' => $this->formatAmount($req->amount),
            'currency' => $req->currency,
            'intent' => $intent,
            'merchantInvoiceNumber' => $this->sanitize($req->reference),
        ];

        if (isset($req->extras['optional_merchant_info'])) {
            $payload['optionalMerchantInfo'] = $this->sanitize((string) $req->extras['optional_merchant_info']);
        }

        if ($agreementId) {
            $payload['agreementID'] = $agreementId;
        }

        return $payload;
    }
}
