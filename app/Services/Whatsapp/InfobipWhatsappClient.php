<?php

namespace App\Services\Whatsapp;

use App\Models\WhatsappMessage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Client for Infobip's WhatsApp API ("Authorization: App {key}"). Delivery and
 * seen reports come back to InfobipWebhookController through the notifyUrl
 * sent with every message.
 */
class InfobipWhatsappClient implements WhatsappGateway
{
    /**
     * Infobip status groups on a send response or delivery report.
     */
    public const STATUS_GROUPS = [
        'PENDING' => WhatsappMessage::STATUS_QUEUED,
        'DELIVERED' => WhatsappMessage::STATUS_DELIVERED,
        'UNDELIVERABLE' => WhatsappMessage::STATUS_FAILED,
        'EXPIRED' => WhatsappMessage::STATUS_FAILED,
        'REJECTED' => WhatsappMessage::STATUS_FAILED,
    ];

    public function senders(): array
    {
        $registered = collect($this->request('GET', 'whatsapp/2/senders')->json('results', []))
            ->keyBy('sender');

        return array_map(function (string $sender) use ($registered): array {
            $number = WhatsappSettings::infobipNumber($sender);

            return [
                'sender' => $sender,
                'connected' => $number !== null && ($registered[$number]['connectionStatus'] ?? null) === 'CONNECTED',
                'display_phone_number' => $number,
            ];
        }, array_keys(WhatsappSettings::EVENTS));
    }

    public function templates(string $sender): array
    {
        $number = WhatsappSettings::infobipNumber($sender);

        if ($number === null) {
            return [];
        }

        $templates = $this->request('GET', 'whatsapp/2/senders/'.rawurlencode($number).'/templates')->json('templates', []);

        return array_values(array_map(fn (array $template): array => [
            'template' => (string) $template['name'],
            'language' => (string) $template['language'],
            'body_param_count' => self::placeholderCount((string) ($template['structure']['body']['text'] ?? '')),
            'has_url_button' => collect($template['structure']['buttons'] ?? [])
                ->contains(fn (array $button): bool => ($button['type'] ?? null) === 'URL' && str_contains((string) ($button['url'] ?? ''), '{{1}}')),
        ], array_filter($templates, fn (array $template): bool => ($template['status'] ?? null) === 'APPROVED')));
    }

    public function send(
        string $sender,
        string $to,
        string $template,
        string $language,
        array $bodyParams,
        ?string $buttonUrlSuffix,
        ?string $reference,
        string $idempotencyKey,
    ): array {
        $from = WhatsappSettings::infobipNumber($sender)
            ?? throw new WhatsappGatewayException("No Infobip WhatsApp number is set for the {$sender} sender.");

        $templateData = ['body' => ['placeholders' => array_values(array_map('strval', $bodyParams))]];

        if (filled($buttonUrlSuffix) && $this->hasUrlButton($sender, $template, $language)) {
            $templateData['buttons'] = [['type' => 'URL', 'parameter' => $buttonUrlSuffix]];
        }

        $result = $this->request('POST', 'whatsapp/1/message/template', [
            'messages' => [[
                'from' => $from,
                'to' => ltrim($to, '+'),
                'content' => [
                    'templateName' => $template,
                    'templateData' => $templateData,
                    'language' => $language,
                ],
                'callbackData' => $reference,
                'notifyUrl' => WhatsappSettings::infobipWebhookUrl(),
            ]],
        ])->json('messages.0', []);

        $group = $result['status']['groupName'] ?? 'PENDING';
        $status = self::STATUS_GROUPS[$group] ?? WhatsappMessage::STATUS_QUEUED;

        if ($status === WhatsappMessage::STATUS_FAILED) {
            throw new WhatsappGatewayException($result['status']['description'] ?? "Infobip rejected the message ({$group}).");
        }

        return ['id' => $result['messageId'] ?? null, 'status' => $status];
    }

    /**
     * Number of distinct {{n}} placeholders in a template body.
     */
    public static function placeholderCount(string $body): int
    {
        preg_match_all('/\{\{\s*(\d+)\s*\}\}/', $body, $matches);

        return count(array_unique($matches[1]));
    }

    /**
     * Infobip rejects a button parameter for a template without a dynamic URL
     * button, so check the (briefly cached) template before adding one.
     */
    private function hasUrlButton(string $sender, string $template, string $language): bool
    {
        try {
            $templates = Cache::remember(
                'infobip:whatsapp-templates:'.WhatsappSettings::infobipNumber($sender),
                600,
                fn (): array => $this->templates($sender)
            );
        } catch (WhatsappGatewayException) {
            return false;
        }

        return collect($templates)->contains(fn (array $candidate): bool => $candidate['template'] === $template
            && $candidate['language'] === $language
            && $candidate['has_url_button']);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     *
     * @throws WhatsappGatewayException on a connection failure or a non-2xx response
     */
    private function request(string $method, string $path, ?array $payload = null): Response
    {
        $apiKey = WhatsappSettings::infobipApiKey();

        if (! $apiKey) {
            throw new WhatsappGatewayException('Infobip WhatsApp is not configured.');
        }

        $pending = Http::timeout(15)
            ->acceptJson()
            ->withHeaders(['Authorization' => 'App '.$apiKey]);

        $url = WhatsappSettings::infobipBaseUrl().'/'.$path;

        try {
            $response = $method === 'GET' ? $pending->get($url) : $pending->send($method, $url, ['json' => $payload]);
        } catch (ConnectionException $e) {
            throw new WhatsappGatewayException('Infobip unreachable: '.$e->getMessage());
        }

        if (! $response->successful()) {
            throw new WhatsappGatewayException(
                $response->json('requestError.serviceException.text') ?? "Infobip returned HTTP {$response->status()}",
                $response->status()
            );
        }

        return $response;
    }
}
