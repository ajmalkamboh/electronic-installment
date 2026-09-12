<?php

namespace App\Exceptions;

use Exception;

class FeatureDisabledException extends Exception
{
    public function __construct(
        string $message = 'This feature is not available in your current subscription tier.',
        public ?string $featureKey = null,
        int $code = 403,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
