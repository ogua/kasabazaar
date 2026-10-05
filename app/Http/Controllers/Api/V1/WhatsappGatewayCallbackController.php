<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Receiver;
use App\Models\User;
use App\Models\WhatsappMessage;
use App\Services\Whatsapp\PhoneNumber;
use App\Services\Whatsapp\WhatsappSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Events from the Ogua WhatsApp gateway: delivery status of messages we sent
 * (a failure triggers the SMS fallback), and opt-outs/opt-ins when a recipient
 * replies STOP/START. Signed with the callback secret from the partner portal:
 * X-Signature = HMAC-SHA256(secret, "{X-Timestamp}.{raw body}").
 */
class WhatsappGatewayCallbackController extends Controller
{
    private const MAX_CLOCK_SKEW_SECONDS = 300;

    /**
     * Gateway statuses only move forward; a late "sent" never overwrites "delivered".
     */
    private const STATUS_RANK = [
        WhatsappMessage::STATUS_PENDING => 0,
        WhatsappMessage::STATUS_QUEUED => 1,
        WhatsappMessage::STATUS_SENT => 2,
        WhatsappMessage::STATUS_DELIVERED => 3,
        WhatsappMessage::STATUS_READ => 4,
        WhatsappMessage::STATUS_FAILED => 5,
    ];

    public function handle(Request $request): JsonResponse
    {
        if (! $this->hasValidSignature($request)) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $event = (string) $request->input('event');
        $data = (array) $request->input('data', []);

        match ($event) {
            'message.status' => $this->messageStatus($data),
            'contact.opted_out' => $this->setOptOut((string) ($data['phone'] ?? ''), true),
            'contact.opted_in' => $this->setOptOut((string) ($data['phone'] ?? ''), false),
            default => null,
        };

        return response()->json(['status' => 'ok']);
    }

    private function hasValidSignature(Request $request): bool
    {
        $secret = (string) WhatsappSettings::callbackSecret();
        $timestamp = (string) $request->header('X-Timestamp');
        $signature = (string) $request->header('X-Signature');

        if ($secret === '' || ! ctype_digit($timestamp) || abs(time() - (int) $timestamp) > self::MAX_CLOCK_SKEW_SECONDS) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret), $signature);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function messageStatus(array $data): void
    {
        $message = WhatsappMessage::where('gateway_message_id', $data['id'] ?? null)->first();
        $status = (string) ($data['status'] ?? '');

        if (! $message || ! isset(self::STATUS_RANK[$status])) {
            return;
        }

        if (self::STATUS_RANK[$status] <= (self::STATUS_RANK[$message->status] ?? -1)) {
            return;
        }

        if ($status === WhatsappMessage::STATUS_FAILED) {
            $message->markFailed($data['error_code'] ?? null, $data['error_message'] ?? null);
            $message->sendFallbackSms();

            return;
        }

        $message->update(['status' => $status]);
    }

    /**
     * Apply a STOP (or START) to every client, receiver and user with this number.
     */
    private function setOptOut(string $phone, bool $optedOut): void
    {
        $variants = PhoneNumber::storedVariants($phone);

        if ($variants === []) {
            return;
        }

        $changes = $optedOut
            ? ['whatsapp_opted_out_at' => now()]
            : ['whatsapp_opted_out_at' => null, 'whatsapp_opt_in_at' => now()];

        Client::whereIn('phone', $variants)->update($changes);
        Receiver::whereIn('receiver_phone', $variants)->update($changes);
        User::whereIn('phone', $variants)->update($changes);
    }
}
