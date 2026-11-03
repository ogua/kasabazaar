<?php

namespace App\Services\Whatsapp;

/**
 * A WhatsApp provider (Infobip by default, or the Ogua gateway — see
 * WhatsappSettings::provider()). Senders are our own keys ("logistics",
 * "marketplace"); each provider maps them to its own numbers.
 */
interface WhatsappGateway
{
    /**
     * @return list<array{sender: string, connected: bool, display_phone_number: ?string}>
     *
     * @throws WhatsappGatewayException
     */
    public function senders(): array;

    /**
     * Approved templates for a sender.
     *
     * @return list<array{template: string, language: string, body_param_count: int, has_url_button: bool}>
     *
     * @throws WhatsappGatewayException
     */
    public function templates(string $sender): array;

    /**
     * Send a template message.
     *
     * @param  list<string>  $bodyParams
     * @return array{id?: ?string, status?: string} the provider's message id and one of the WhatsappMessage statuses
     *
     * @throws WhatsappGatewayException
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
    ): array;
}
