<?php

namespace DevWizard\Payify\Providers\Sslcommerz;

use DevWizard\Payify\Dto\WebhookPayload;
use DevWizard\Payify\Exceptions\IpNotAllowedException;
use DevWizard\Payify\Exceptions\WebhookVerificationException;
use Illuminate\Http\Request;

class SslcommerzIpnVerifier
{
    public function __construct(
        private array $config,
        private SslcommerzValidator $validator,
    ) {}

    public function verify(Request $request): WebhookPayload
    {
        if ($this->config['security']['verify_ip'] ?? true) {
            $this->assertIpAllowed($request);
        }
        if ($this->config['security']['verify_signature'] ?? true) {
            $this->assertSignatureValid($request);
        }
        if ($this->config['security']['verify_validator'] ?? true) {
            $this->assertValidatorAgrees($request);
        }

        return $this->build($request);
    }

    private function assertIpAllowed(Request $request): void
    {
        $mode = $this->config['mode'] ?? 'sandbox';
        $allowed = $this->config['security']['allowed_ips_'.$mode] ?? [];

        if (! in_array($request->ip(), $allowed, true)) {
            throw new IpNotAllowedException("Source IP [{$request->ip()}] not allowed for [{$mode}]");
        }
    }

    private function assertSignatureValid(Request $request): void
    {
        $verifyKey = $request->input('verify_key');
        $verifySign = $request->input('verify_sign');

        if (! $verifyKey || ! $verifySign) {
            throw new WebhookVerificationException('Missing verify_sign/verify_key', reason: 'missing_fields');
        }

        // SSLCommerz IPN hash per official SDK (lib/SslCommerzNotification.php::SSLCOMMERZ_hash_verify):
        //   1. Parse comma-separated field names from verify_key
        //   2. Pull corresponding posted values into an assoc map
        //   3. Add 'store_passwd' => md5(store_passwd)
        //   4. ksort alphabetically
        //   5. Build querystring key=value&key=value (no trailing &)
        //   6. md5(string) must equal verify_sign
        $fields = array_map('trim', explode(',', (string) $verifyKey));

        $data = [];
        foreach ($fields as $field) {
            if ($field === '') {
                continue;
            }
            $data[$field] = (string) $request->input($field, '');
        }
        $data['store_passwd'] = md5((string) ($this->config['credentials']['store_passwd'] ?? ''));

        ksort($data);

        $hashString = '';
        foreach ($data as $key => $value) {
            $hashString .= $key.'='.$value.'&';
        }
        $hashString = rtrim($hashString, '&');

        if (! hash_equals(md5($hashString), strtolower((string) $verifySign))) {
            throw new WebhookVerificationException('IPN signature mismatch', reason: 'signature_mismatch');
        }
    }

    private function assertValidatorAgrees(Request $request): void
    {
        $valId = $request->input('val_id');
        if (! $valId) {
            throw new WebhookVerificationException('Missing val_id', reason: 'missing_val_id');
        }

        $validation = $this->validator->validateByValId((string) $valId);
        $status = (string) ($validation['status'] ?? '');

        $validatorAmount = isset($validation['amount']) ? (float) $validation['amount'] : null;
        $requestAmount = $request->has('amount') ? (float) $request->input('amount') : null;
        $amountMatches = $validatorAmount !== null && $requestAmount !== null
            && abs($validatorAmount - $requestAmount) < 0.01;

        if (! in_array($status, [Constants::STATUS_VALID, Constants::STATUS_VALIDATED], true) || ! $amountMatches) {
            throw new WebhookVerificationException('Validator recheck failed', reason: 'validator_disagreement');
        }
    }

    private function build(Request $request): WebhookPayload
    {
        $status = (string) $request->input('status', '');
        $event = match ($status) {
            Constants::STATUS_VALID, Constants::STATUS_VALIDATED => 'payment.succeeded',
            Constants::STATUS_FAILED => 'payment.failed',
            Constants::STATUS_CANCELLED => 'payment.cancelled',
            default => 'payment.unknown',
        };

        return new WebhookPayload(
            provider: 'sslcommerz',
            event: $event,
            providerTransactionId: (string) ($request->input('bank_tran_id', '')),
            reference: (string) ($request->input('tran_id', '')),
            amount: $request->has('amount') ? (float) $request->input('amount') : null,
            currency: (string) ($request->input('currency', 'BDT')),
            raw: $request->all(),
            verified: true,
        );
    }
}
