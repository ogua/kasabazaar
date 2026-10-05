<?php

namespace App\Models\Concerns;

/**
 * WhatsApp opt-in state for a message recipient (client, receiver or user).
 * An opt-out (a STOP reply relayed by the gateway) always wins over an opt-in.
 */
trait HasWhatsappConsent
{
    public function initializeHasWhatsappConsent(): void
    {
        $this->mergeCasts([
            'whatsapp_opt_in_at' => 'datetime',
            'whatsapp_opted_out_at' => 'datetime',
        ]);
    }

    public function hasWhatsappConsent(): bool
    {
        return $this->whatsapp_opt_in_at !== null && $this->whatsapp_opted_out_at === null;
    }

    /**
     * Record an opt-in (or withdraw it). Opting in again clears an earlier opt-out.
     */
    public function setWhatsappConsent(bool $consents): void
    {
        $this->forceFill($consents
            ? ['whatsapp_opt_in_at' => $this->whatsapp_opt_in_at ?? now(), 'whatsapp_opted_out_at' => null]
            : ['whatsapp_opt_in_at' => null]
        )->save();
    }
}
