<?php

namespace App\Services\Whatsapp;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Client for the Ogua WhatsApp partner API. Every request is signed:
 * X-Signature = HMAC-SHA256(client secret, "{timestamp}.{METHOD}.{path}.{body}"),
 * where path is relative to the gateway root, e.g. "api/partner/v1/whatsapp/messages".
 */
class OguaWhatsappClient
{
    private const API_PREFIX = 'api/partner/v1/whatsapp';

    /**
     * @return list<array<string, mixed>>
     */
    public function senders(): array
    {
        return $this->request('GET', 'senders')->json('data', []);
    }

    /**
     * Approved templates for a sender: template, language, body_text, body_param_count, has_url_button.
     *
     * @return list<array<string, mixed>>
     */
    public function templates(string $sender): array
    {
        return $this->request('GET', 'senders/'.rawurlencode($sender).'/templates')->json('data', []);
    }

    /**
     * Queue a template message. Repeating an idempotency key returns the original message.
     *
     * @param  list<string>  $bodyParams
     * @return array<string, mixed> the gateway's message: id, status, …
     */
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
        return $this->request('POST', 'messages', [
            'sender' => $sender,
            'to' => $to,
            'template' => $template,
            'language' => $language,
            'body_params' => array_values($bodyParams),
            'button_url_suffix' => $buttonUrlSuffix,
            'reference' => $reference,
            'idempotency_key' => $idempotencyKey,
        ])->json('data', []);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     *
     * @throws WhatsappGatewayException on a connection failure or a non-2xx response
     */
    private function request(string $method, string $endpoint, ?array $payload = null): Response
    {
        $baseUrl = WhatsappSettings::gatewayUrl();
        $path = self::API_PREFIX.'/'.$endpoint;
        $body = $payload === null ? '' : json_encode($payload, JSON_UNESCAPED_SLASHES);
        $timestamp = (string) time();

        if (! $baseUrl || ! WhatsappSettings::clientKey() || ! WhatsappSettings::clientSecret()) {
            throw new WhatsappGatewayException('WhatsApp gateway is not configured.');
        }

        $pending = Http::timeout(10)
            ->acceptJson()
            ->withHeaders([
                'X-Client-Key' => WhatsappSettings::clientKey(),
                'X-Timestamp' => $timestamp,
                'X-Signature' => hash_hmac('sha256', "{$timestamp}.{$method}.{$path}.{$body}", WhatsappSettings::clientSecret()),
            ]);

        try {
            $response = $method === 'GET'
                ? $pending->get("{$baseUrl}/{$path}")
                : $pending->withBody($body, 'application/json')->send($method, "{$baseUrl}/{$path}");
        } catch (ConnectionException $e) {
            throw new WhatsappGatewayException('WhatsApp gateway unreachable: '.$e->getMessage());
        }

        if (! $response->successful()) {
            throw new WhatsappGatewayException(
                $response->json('message') ?? "WhatsApp gateway returned HTTP {$response->status()}",
                $response->status()
            );
        }

        return $response;
    }
}
