<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WhatsappMessage;
use App\Services\Whatsapp\WhatsappOptOut;
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

    public function handle(Request $request): JsonResponse
    {
        if (! $this->hasValidSignature($request)) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $event = (string) $request->input('event');
        $data = (array) $request->input('data', []);

        match ($event) {
            'message.status' => WhatsappMessage::applyStatusReport(
                $data['id'] ?? null,
                (string) ($data['status'] ?? ''),
                $data['error_code'] ?? null,
                $data['error_message'] ?? null,
            ),
            'contact.opted_out' => WhatsappOptOut::apply((string) ($data['phone'] ?? ''), true),
            'contact.opted_in' => WhatsappOptOut::apply((string) ($data['phone'] ?? ''), false),
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
}
