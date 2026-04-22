<?php

namespace DevWizard\Payify\Http\Controllers;

use DevWizard\Payify\Exceptions\ProviderNotFoundException;
use DevWizard\Payify\Managers\PayifyManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CallbackController
{
    public function __invoke(Request $request, string $provider, ?string $result, PayifyManager $manager): Response|JsonResponse|RedirectResponse
    {
        try {
            $driver = $manager->provider($provider);
        } catch (ProviderNotFoundException) {
            return response()->json(['error' => 'Unknown provider'], 400);
        }

        $response = $driver->handleCallback($request);

        $redirectUrl = config('payify.callback.redirect_url');

        if ($redirectUrl) {
            return redirect()->to($redirectUrl.'?'.http_build_query([
                'status' => $response->status->value,
                'transaction' => $response->transactionId,
            ]));
        }

        return response()->json([
            'status' => $response->status->value,
            'transaction' => $response->transactionId,
            'provider_transaction' => $response->providerTransactionId,
        ]);
    }
}
