<?php

namespace DevWizard\Payify\Providers\Bkash;

final class Constants
{
    public const PATH_GRANT = '/tokenized/checkout/token/grant';

    public const PATH_REFRESH = '/tokenized/checkout/token/refresh';

    public const PATH_CREATE = '/tokenized/checkout/create';

    public const PATH_EXECUTE = '/tokenized/checkout/execute';

    public const PATH_STATUS = '/tokenized/checkout/payment/status';

    public const PATH_SEARCH = '/tokenized/checkout/general/searchTransaction';

    public const PATH_REFUND = '/tokenized/checkout/payment/refund';

    public const PATH_CAPTURE = '/tokenized/checkout/payment/confirm/capture';

    public const PATH_VOID = '/tokenized/checkout/payment/confirm/void';

    public const PATH_AGREEMENT_CANCEL = '/tokenized/checkout/agreement/cancel';

    public const PATH_PAYOUT_INIT = '/tokenized/payout/initiate';

    public const PATH_PAYOUT_EXECUTE = '/tokenized/payout/execute';

    public const MODE_CHECKOUT = '0011';

    public const MODE_AGREEMENT_CREATE = '0000';

    public const MODE_AGREEMENT_PAY = '0001';

    public const INTENT_SALE = 'sale';

    public const INTENT_AUTH = 'authorization';

    public const STATUS_SUCCESS = '0000';

    public const TXN_STATUS_INITIATED = 'Initiated';

    public const TXN_STATUS_COMPLETED = 'Completed';

    public const TXN_STATUS_AUTHORIZED = 'Authorized';

    public const TXN_STATUS_FAILED = 'Failed';

    public const TXN_STATUS_CANCELLED = 'Cancelled';

    public const ERR_INVALID_TOKEN = '2079';
}
