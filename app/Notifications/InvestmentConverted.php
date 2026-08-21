<?php

namespace App\Notifications;

use App\Models\InvestmentConversion;
use App\Models\InvestmentConversionSource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InvestmentConverted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly InvestmentConversion $conversion,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $target = $this->conversion->targetInvestment;
        $sourceReferences = $this->conversion->sources
            ->map(fn (InvestmentConversionSource $source) => $source->sourceInvestment?->reference)
            ->filter()
            ->implode(', ');

        return [
            'title' => 'Capital Converted',
            'body' => sprintf(
                '%s from %s has been converted into %s tranche %s. A new agreement is ready for your review.',
                '$'.number_format((float) $this->conversion->total_amount, 2),
                $sourceReferences,
                strtolower($this->conversion->direction->targetCapitalType()->getLabel()),
                $target?->reference ?? ''
            ),
            'type' => 'investment_converted',
            'conversion_id' => $this->conversion->id,
            'conversion_reference' => $this->conversion->reference,
            'direction' => $this->conversion->direction->value,
            'target_investment_id' => $target?->id,
            'amount' => (float) $this->conversion->total_amount,
        ];
    }
}
