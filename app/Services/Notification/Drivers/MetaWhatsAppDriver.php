<?php

namespace App\Services\Notification\Drivers;

use App\Services\Notification\Contracts\NotificationDriverInterface;
use Illuminate\Support\Facades\Http;
use Throwable;

class MetaWhatsAppDriver implements NotificationDriverInterface
{
    public function send(string $to, string $message, array $options = []): array
    {
        $phoneNumberId = $options['phone_number_id'] ?? null;
        $accessToken = $options['access_token'] ?? null;

        if (! $phoneNumberId || ! $accessToken) {
            return [
                'success' => false,
                'reference' => null,
                'response' => [],
                'error' => 'Meta WhatsApp Phone Number ID and Access Token are required.',
            ];
        }

        try {
            // Meta expects number without '+' e.g. 923001234567
            $recipientNumber = ltrim($to, '+');

            $url = "https://graph.facebook.com/v19.0/{$phoneNumberId}/messages";

            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $recipientNumber,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $message,
                ],
            ];

            $response = Http::withToken($accessToken)
                ->timeout(10)
                ->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $ref = $data['messages'][0]['id'] ?? ('WAM-' . uniqid());

                return [
                    'success' => true,
                    'reference' => $ref,
                    'response' => $data,
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'reference' => null,
                'response' => $response->json() ?? [],
                'error' => $response->json('error.message') ?? "WhatsApp Cloud API HTTP {$response->status()}",
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'reference' => null,
                'response' => [],
                'error' => 'WhatsApp Cloud API Exception: ' . $e->getMessage(),
            ];
        }
    }
}
