<?php

namespace DevWizard\Payify\Http\Controllers;

use DevWizard\Payify\Contracts\HandlesWebhook;
use DevWizard\Payify\Dto\RefundResponse;
use DevWizard\Payify\Dto\WebhookPayload;
use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Events\PaymentCancelled;
use DevWizard\Payify\Events\PaymentFailed;
use DevWizard\Payify\Events\PaymentRefunded;
use DevWizard\Payify\Events\PaymentSucceeded;
use DevWizard\Payify\Events\WebhookReceived;
use DevWizard\Payify\Exceptions\WebhookVerificationException;
use DevWizard\Payify\Jobs\ProcessWebhookJob;
use DevWizard\Payify\Managers\PayifyManager;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WebhookController
{
    public function __invoke(Request $request, string $provider, PayifyManager $manager): Response
    {
        $driver = $manager->provider($provider);

        if (! $driver instanceof HandlesWebhook) {
            $this->log()->warning('payify.webhook.unsupported', ['provider' => $provider]);

            return response('unsupported', 400);
        }

        try {
            $payload = $driver->verifyWebhook($request);
        } catch (WebhookVerificationException $e) {
            $this->log()->warning('payify.webhook.verify_failed', [
                'provider' => $provider,
                'reason' => $e->reason(),
                'message' => $e->getMessage(),
            ]);

            return response('invalid signature', 400);
        }

        $txn = $payload->reference
            ? Transaction::where('provider', $provider)->where('reference', $payload->reference)->first()
            : null;

        if ($txn) {
            $txn->update([
                'webhook_payload' => $payload->raw,
                'webhook_verified_at' => now(),
                'provider_transaction_id' => $txn->provider_transaction_id ?? $payload->providerTransactionId,
            ]);
        }

        $queue = config('payify.webhooks.queue');

        if ($queue) {
            ProcessWebhookJob::dispatch($payload, $txn?->id)->onQueue($queue);

            return response('ok', 200);
        }

        if ($txn) {
            self::applyStatusTransition($txn, $payload);
        }

        event(new WebhookReceived($payload, $txn));

        return response('ok', 200);
    }

    public static function applyStatusTransition(Transaction $txn, WebhookPayload $payload): void
    {
        if ($payload->event === 'payment.refunded') {
            if (! $txn->canRefund()) {
                return;
            }
        } elseif ($txn->status instanceof TransactionStatus && $txn->status->isTerminal()) {
            return;
        }

        $code = $payload->raw['error_code'] ?? 'PROVIDER_FAILURE';
        $message = $payload->raw['error_message'] ?? 'Payment failed';

        match ($payload->event) {
            'payment.succeeded' => $txn->markSucceeded($payload->providerTransactionId, $payload->raw),
            'payment.failed' => $txn->markFailed($code, $message, $payload->raw),
            'payment.cancelled' => $txn->markCancelled($payload->raw),
            'payment.refunded' => $txn->markRefunded($payload->amount ?? (float) $txn->amount, $payload->raw),
            default => null,
        };

        $event = match ($payload->event) {
            'payment.succeeded' => new PaymentSucceeded($txn->fresh()),
            'payment.failed' => new PaymentFailed($txn->fresh(), $code, $message),
            'payment.cancelled' => new PaymentCancelled($txn->fresh()),
            'payment.refunded' => new PaymentRefunded($txn->fresh(), RefundResponse::fromWebhook($payload, $txn->id)),
            default => null,
        };

        if ($event) {
            event($event);
        }
    }

    private function log()
    {
        return Log::channel(config('payify.log_channel'));
    }
}
