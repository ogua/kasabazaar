<?php

namespace App\Notifications;

use App\Models\InvestmentInterestPayout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InvestmentInterestPayoutStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly InvestmentInterestPayout $payout,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Interest Payout '.$this->payout->status->getLabel(),
            'body' => $this->bodyForStatus(),
            'type' => 'investment_interest_payout_status_updated',
            'interest_payout_id' => $this->payout->id,
            'investment_id' => $this->payout->investment_id,
            'status' => $this->payout->status->value,
        ];
    }

    private function bodyForStatus(): string
    {
        $reference = $this->payout->investment?->reference ?? $this->payout->investment_id;
        $period = $this->payout->period_start?->format('M j, Y').' – '.$this->payout->period_end?->format('M j, Y');

        return match ($this->payout->status->value) {
            'processing' => "A payment is being processed for the interest due on investment {$reference} for {$period}.",
            'paid' => "Your interest payment for investment {$reference}, period {$period}, has been paid: \$".number_format((float) $this->payout->amount_paid, 2).'.',
            'skipped' => "The interest due on investment {$reference} for {$period} was marked skipped: {$this->payout->notes}",
            'reversed' => "A previously paid interest payment for investment {$reference}, period {$period}, was reversed: {$this->payout->notes}",
            default => "Interest payout for investment {$reference}, period {$period}, is now {$this->payout->status->getLabel()}.",
        };
    }
}
