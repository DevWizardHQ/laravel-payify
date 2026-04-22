<?php

namespace DevWizard\Payify\Contracts;

use DevWizard\Payify\Dto\WebhookPayload;
use Illuminate\Http\Request;

interface HandlesWebhook
{
    public function verifyWebhook(Request $request): WebhookPayload;
}
