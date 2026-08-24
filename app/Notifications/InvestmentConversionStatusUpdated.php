<?php

namespace App\Notifications;

use App\Models\InvestmentConversion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Covers every conversion decision the investor does not make themselves —
 * approval, rejection, and the reversal of an already-executed conversion.
 * Execution has its own notification, InvestmentConverted, because it carries
 * the successor tranche and its agreement.
 */
class InvestmentConversionStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly InvestmentConversion $conversion,
        public readonly ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Conversion Request '.$this->conversion->status->getLabel(),
            'body' => $this->bodyForStatus(),
            'type' => 'investment_conversion_status_updated',
            'conversion_id' => $this->conversion->id,
            'conversion_reference' => $this->conversion->reference,
            'direction' => $this->conversion->direction->value,
            'status' => $this->conversion->status->value,
        ];
    }

    private function bodyForStatus(): string
    {
        $reference = $this->conversion->reference;
        $target = strtolower($this->conversion->direction->targetCapitalType()->getLabel());

        return match ($this->conversion->status->value) {
            'approved' => "Your request to convert your capital into a {$target} tranche ({$reference}) has been approved and is queued for settlement.",
            'rejected' => "Your request to convert your capital into a {$target} tranche ({$reference}) was rejected: {$this->conversion->rejection_reason}",
            // Only the reversal path notifies on a cancelled conversion — an investor
            // cancelling their own pending request needs no notice of their own action.
            'cancelled' => "Conversion {$reference} has been reversed: {$this->reason} Your original tranche(s) have been restored to their pre-conversion balances, and any agreement issued for the successor tranche is void.",
            default => "Conversion {$reference} is now {$this->conversion->status->getLabel()}.",
        };
    }
}
