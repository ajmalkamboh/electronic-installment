<?php

namespace App\Services\Notification\Contracts;

interface NotificationDriverInterface
{
    /**
     * Send a notification message to the recipient.
     *
     * @param  string  $to       Normalized recipient phone number
     * @param  string  $message  Message text content
     * @param  array   $options  Driver-specific credentials and metadata
     * @return array   Standardized response: ['success' => bool, 'reference' => ?string, 'response' => array, 'error' => ?string]
     */
    public function send(string $to, string $message, array $options = []): array;
}
