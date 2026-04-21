<?php

namespace DevWizard\Payify\Http\Controllers;

use DevWizard\Payify\Managers\PayifyManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CallbackController
{
    public function __invoke(Request $request, string $provider, ?string $result, PayifyManager $manager): Response|RedirectResponse
    {
        $driver = $manager->provider($provider);
        $response = $driver->handleCallback($request);

        $redirectUrl = config('payify.callback.redirect_url');

        if ($redirectUrl) {
            return redirect()->to($redirectUrl.'?status='.$response->status->value.'&transaction='.$response->transactionId);
        }

        return response()->json([
            'status' => $response->status->value,
            'transaction' => $response->transactionId,
            'provider_transaction' => $response->providerTransactionId,
        ]);
    }
}
