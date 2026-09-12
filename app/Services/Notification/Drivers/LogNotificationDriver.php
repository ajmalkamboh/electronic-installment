<?php

namespace App\Services\Notification\Drivers;

use App\Services\Notification\Contracts\NotificationDriverInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogNotificationDriver implements NotificationDriverInterface
{
    public function send(string $to, string $message, array $options = []): array
    {
        $channel = $options['channel'] ?? 'sms';
        $ref = 'SIM-' . strtoupper($channel) . '-' . strtoupper(Str::random(10));

        Log::channel('single')->info("[Simulated {$channel} Notification] To: {$to} | Ref: {$ref} | Content: {$message}");

        return [
            'success' => true,
            'reference' => $ref,
            'response' => [
                'driver' => 'log',
                'channel' => $channel,
                'recipient' => $to,
                'status' => 'delivered',
                'timestamp' => now()->toIso8601String(),
            ],
            'error' => null,
        ];
    }
}
