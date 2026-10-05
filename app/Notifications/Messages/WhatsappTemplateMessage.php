<?php

namespace App\Notifications\Messages;

/**
 * What a notification sends over WhatsApp: which sender number and event (the
 * event decides the template, via WhatsappSettings), the event's body
 * parameters in order, and the suffix for a template's dynamic link button.
 */
class WhatsappTemplateMessage
{
    /**
     * @param  list<string|int|float|null>  $params
     */
    public function __construct(
        public readonly string $sender,
        public readonly string $event,
        public readonly array $params,
        public readonly ?string $buttonUrlSuffix = null,
        public readonly ?string $reference = null,
    ) {}

    /**
     * WhatsApp rejects empty parameters and ones containing newlines, tabs or
     * runs of spaces, so every value is collapsed to one line and never blank.
     *
     * @return list<string>
     */
    public function bodyParams(): array
    {
        return array_map(function (string|int|float|null $value): string {
            $clean = trim(preg_replace('/\s+/', ' ', (string) $value));

            return $clean === '' ? '-' : mb_substr($clean, 0, 1000);
        }, array_values($this->params));
    }
}
