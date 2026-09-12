<?php

namespace App\Exceptions;

use Exception;

class QuotaExceededException extends Exception
{
    public function __construct(
        string $message = 'Subscription plan quota limit exceeded.',
        public ?string $quotaType = null,
        public int $currentUsage = 0,
        public int $maxLimit = 0,
        int $code = 403,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
