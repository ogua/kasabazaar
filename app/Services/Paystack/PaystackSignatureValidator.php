<?php

namespace App\Services\Paystack;

use Illuminate\Http\Request;
use Spatie\WebhookClient\Exceptions\InvalidConfig;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

/**
 * Paystack signs every webhook with an HMAC-SHA512 of the raw body, keyed with the
 * account's secret key, in the `x-paystack-signature` header (spatie's default
 * validator expects SHA-256).
 */
class PaystackSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $signature = (string) $request->header($config->signatureHeaderName);

        if ($signature === '') {
            return false;
        }

        if (empty($config->signingSecret)) {
            throw InvalidConfig::signingSecretNotSet();
        }

        return hash_equals(hash_hmac('sha512', $request->getContent(), $config->signingSecret), $signature);
    }
}
