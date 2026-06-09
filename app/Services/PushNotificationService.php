<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    private string $serverKey;

    public function __construct()
    {
        $this->serverKey = config('services.fcm.server_key', '');
    }

    public function sendToUser(string $userId, string $title, string $body, array $data = []): void
    {
        if (empty($this->serverKey)) {
            Log::debug('FCM server key not configured — skipping push notification.');
            return;
        }

        $tokens = DeviceToken::where('user_id', $userId)->pluck('token')->toArray();

        if (empty($tokens)) {
            return;
        }

        $this->dispatch($tokens, $title, $body, $data);
    }

    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): void
    {
        if (empty($this->serverKey) || empty($tokens)) {
            return;
        }

        $this->dispatch($tokens, $title, $body, $data);
    }

    private function dispatch(array $tokens, string $title, string $body, array $data): void
    {
        $payload = [
            'registration_ids' => $tokens,
            'notification'     => ['title' => $title, 'body' => $body, 'sound' => 'default'],
            'data'             => $data,
        ];

        $response = Http::withToken($this->serverKey)
            ->post('https://fcm.googleapis.com/fcm/send', $payload);

        if (!$response->successful()) {
            Log::warning('FCM push failed', ['status' => $response->status(), 'body' => $response->body()]);
            return;
        }

        $result = $response->json();

        // Remove tokens that FCM reports as invalid
        if (!empty($result['results'])) {
            foreach ($result['results'] as $i => $item) {
                if (!empty($item['error']) && in_array($item['error'], ['NotRegistered', 'InvalidRegistration'])) {
                    DeviceToken::where('token', $tokens[$i])->delete();
                }
            }
        }
    }
}
