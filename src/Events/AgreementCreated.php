<?php

namespace DevWizard\Payify\Events;

use DevWizard\Payify\Models\Agreement;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgreementCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Agreement $agreement) {}
}
