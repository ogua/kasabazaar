<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Sends push notifications to the KasaRose mobile app through the Expo Push API.
 *
 * The app registers Expo push tokens (`ExponentPushToken[...]`), which only Expo's
 * service can deliver to; it relays to FCM and APNs itself, so no Firebase server
 * key is needed. Tokens Expo reports as `DeviceNotRegistered` are pruned.
 */
class PushNotificationService
{
    private const ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    private const BATCH_SIZE = 100;

    public function sendToUser(string $userId, string $title, string $body, array $data = []): void
    {
        $tokens = DeviceToken::where('user_id', $userId)->pluck('token')->all();

        $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * @param  array<int, string>  $tokens
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): void
    {
        $tokens = array_values(array_unique(array_filter($tokens, fn (string $token) => $this->isExpoToken($token))));

        foreach (array_chunk($tokens, self::BATCH_SIZE) as $batch) {
            $this->dispatch($batch, $title, $body, $data);
        }
    }

    public function isExpoToken(string $token): bool
    {
        return Str::startsWith($token, ['ExponentPushToken[', 'ExpoPushToken[']);
    }

    /**
     * @param  array<int, string>  $tokens
     */
    private function dispatch(array $tokens, string $title, string $body, array $data): void
    {
        $messages = array_map(fn (string $token) => [
            'to' => $token,
            'title' => $title,
            'body' => $body,
            'data' => (object) $data,
            'sound' => 'default',
            'priority' => 'high',
            'channelId' => 'default',
        ], $tokens);

        try {
            $request = Http::acceptJson()->asJson()->timeout(15);

            if ($accessToken = config('services.expo.access_token')) {
                $request = $request->withToken($accessToken);
            }

            $response = $request->post(self::ENDPOINT, $messages);
        } catch (\Throwable $e) {
            Log::warning('Expo push request failed', ['error' => $e->getMessage()]);

            return;
        }

        if (! $response->successful()) {
            Log::warning('Expo push failed', ['status' => $response->status(), 'body' => $response->body()]);

            return;
        }

        foreach ($response->json('data', []) as $i => $ticket) {
            if (($ticket['status'] ?? null) === 'error' && ($ticket['details']['error'] ?? null) === 'DeviceNotRegistered') {
                DeviceToken::where('token', $tokens[$i] ?? null)->delete();
            }
        }
    }
}
