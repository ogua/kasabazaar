<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WhatsappMessage;
use App\Services\Whatsapp\InfobipWhatsappClient;
use App\Services\Whatsapp\WhatsappOptOut;
use App\Services\Whatsapp\WhatsappSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Infobip WhatsApp webhooks, all posted as {"results": [...]}: delivery reports
 * (sent per message as its notifyUrl), seen reports and inbound messages (set
 * on the sender in the Infobip portal). A failed delivery triggers the SMS
 * fallback; an inbound STOP/START opts the number out or back in. Infobip does
 * not sign webhooks, so the URL carries a secret token instead.
 */
class InfobipWebhookController extends Controller
{
    public function handle(Request $request, string $token): JsonResponse
    {
        if (! hash_equals(WhatsappSettings::infobipWebhookToken(), $token)) {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        foreach ((array) $request->input('results', []) as $result) {
            if (! is_array($result)) {
                continue;
            }

            match (true) {
                isset($result['message']) => $this->inboundMessage($result),
                isset($result['seenAt']) => WhatsappMessage::applyStatusReport($result['messageId'] ?? null, WhatsappMessage::STATUS_READ),
                isset($result['status']) => $this->deliveryReport($result),
                default => null,
            };
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function deliveryReport(array $result): void
    {
        $group = (string) ($result['status']['groupName'] ?? '');
        $status = InfobipWhatsappClient::STATUS_GROUPS[$group] ?? null;

        if ($status === null) {
            return;
        }

        $error = $result['error'] ?? [];
        $failed = $status === WhatsappMessage::STATUS_FAILED;

        WhatsappMessage::applyStatusReport(
            $result['messageId'] ?? null,
            $status,
            $failed ? (string) ($error['id'] ?? $result['status']['id'] ?? $group) : null,
            $failed ? ($error['description'] ?? $result['status']['description'] ?? null) : null,
        );
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function inboundMessage(array $result): void
    {
        $text = $result['message']['text'] ?? null;

        if (is_string($text) && filled($result['from'] ?? null)) {
            WhatsappOptOut::applyKeyword((string) $result['from'], $text);
        }
    }
}
