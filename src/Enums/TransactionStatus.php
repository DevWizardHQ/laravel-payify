<?php

namespace DevWizard\Payify\Enums;

enum TransactionStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';
    case Expired = 'expired';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Succeeded, self::Failed, self::Cancelled, self::Refunded, self::Expired => true,
            self::Pending, self::Processing, self::PartiallyRefunded => false,
        };
    }

    public function canRefund(): bool
    {
        return $this === self::Succeeded || $this === self::PartiallyRefunded;
    }
}
