<?php

namespace App\Services\Notification\Drivers;

use App\Services\Notification\Contracts\NotificationDriverInterface;
use Illuminate\Support\Facades\Http;
use Throwable;

class GenericHttpSmsDriver implements NotificationDriverInterface
{
    public function send(string $to, string $message, array $options = []): array
    {
        $endpoint = $options['endpoint_url'] ?? null;
        $apiKey = $options['api_key'] ?? null;
        $apiSecret = $options['api_secret'] ?? null;
        $senderId = $options['sender_id'] ?? 'SHOWROOM';

        if (! $endpoint) {
            return [
                'success' => false,
                'reference' => null,
                'response' => [],
                'error' => 'SMS Gateway endpoint URL is not configured.',
            ];
        }

        try {
            $payload = [
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
                'to' => $to,
                'sender' => $senderId,
                'message' => $message,
                'type' => 'transactional',
            ];

            $response = Http::timeout(10)->post($endpoint, $payload);

            if ($response->successful()) {
                $data = $response->json() ?? ['raw' => $response->body()];
                $ref = $data['message_id'] ?? $data['id'] ?? $data['reference'] ?? ('SMS-' . uniqid());

                return [
                    'success' => true,
                    'reference' => (string) $ref,
                    'response' => is_array($data) ? $data : ['body' => $data],
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'reference' => null,
                'response' => $response->json() ?? ['status' => $response->status()],
                'error' => "Gateway returned HTTP {$response->status()}: " . substr($response->body(), 0, 200),
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'reference' => null,
                'response' => [],
                'error' => 'Gateway request exception: ' . $e->getMessage(),
            ];
        }
    }
}
