<?php

namespace DevWizard\Payify\Providers\Sslcommerz;

final class Constants
{
    public const PATH_INIT = '/gwprocess/v4/api.php';

    public const PATH_VALIDATOR = '/validator/api/validationserverAPI.php';

    public const PATH_TRANSACTION = '/validator/api/merchantTransIDvalidationAPI.php';

    public const PATH_REFUND = '/validator/api/merchantTransIDvalidationAPI.php';

    public const STATUS_VALID = 'VALID';

    public const STATUS_VALIDATED = 'VALIDATED';

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_FAILED = 'FAILED';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUS_INVALID_TRANSACTION = 'INVALID_TRANSACTION';

    public const API_CONNECT_DONE = 'DONE';

    public const API_CONNECT_FAILED = 'FAILED';

    public const API_CONNECT_INVALID = 'INVALID_REQUEST';

    public const API_CONNECT_INACTIVE = 'INACTIVE';

    public const REFUND_STATUS_REFUNDED = 'refunded';

    public const REFUND_STATUS_PROCESSING = 'processing';

    public const REFUND_STATUS_CANCELLED = 'cancelled';

    public const EMBED_SANDBOX_SCRIPT = 'https://sandbox.sslcommerz.com/embed.min.js?0.0.1';

    public const EMBED_LIVE_SCRIPT = 'https://seamless-epay.sslcommerz.com/embed.min.js?0.0.1';
}
