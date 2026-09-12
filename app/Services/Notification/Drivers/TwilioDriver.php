<?php

namespace App\Services\Notification\Drivers;

use App\Services\Notification\Contracts\NotificationDriverInterface;
use Illuminate\Support\Facades\Http;
use Throwable;

class TwilioDriver implements NotificationDriverInterface
{
    public function send(string $to, string $message, array $options = []): array
    {
        $sid = $options['sid'] ?? null;
        $token = $options['token'] ?? null;
        $from = $options['from'] ?? null;
        $channel = $options['channel'] ?? 'sms'; // 'sms' or 'whatsapp'

        if (! $sid || ! $token || ! $from) {
            return [
                'success' => false,
                'reference' => null,
                'response' => [],
                'error' => 'Twilio SID, Auth Token, and From Number are required.',
            ];
        }

        try {
            $formattedTo = $channel === 'whatsapp' ? ('whatsapp:' . $to) : $to;
            $formattedFrom = $channel === 'whatsapp' ? ('whatsapp:' . $from) : $from;

            $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

            $response = Http::withBasicAuth($sid, $token)
                ->asForm()
                ->timeout(10)
                ->post($url, [
                    'To' => $formattedTo,
                    'From' => $formattedFrom,
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'reference' => $data['sid'] ?? null,
                    'response' => $data,
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'reference' => null,
                'response' => $response->json() ?? [],
                'error' => $response->json('message') ?? "Twilio HTTP {$response->status()}",
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'reference' => null,
                'response' => [],
                'error' => 'Twilio API Exception: ' . $e->getMessage(),
            ];
        }
    }
}
