<?php

namespace App\Notifications;

use App\Models\InvestmentWithdrawalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InvestmentWithdrawalStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly InvestmentWithdrawalRequest $request,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Withdrawal Request '.$this->request->status->getLabel(),
            'body' => $this->bodyForStatus(),
            'type' => 'investment_withdrawal_status_updated',
            'withdrawal_request_id' => $this->request->id,
            'investment_id' => $this->request->investment_id,
            'status' => $this->request->status->value,
        ];
    }

    private function bodyForStatus(): string
    {
        $reference = $this->request->investment?->reference ?? $this->request->investment_id;

        return match ($this->request->status->value) {
            'under_review' => "Your withdrawal request for investment {$reference} is now under review.",
            'approved' => "Your withdrawal request for investment {$reference} has been approved.".
                ($this->request->deferral_days ? " Payment is deferred by {$this->request->deferral_days} day(s): {$this->request->deferral_reason}" : ''),
            'rejected' => "Your withdrawal request for investment {$reference} was rejected: {$this->request->rejection_reason}",
            'processing' => "A partial payment has been made toward your withdrawal request for investment {$reference}. Amount paid so far: \$".number_format((float) $this->request->amount_paid, 2).'.',
            'paid' => "Your withdrawal request for investment {$reference} has been fully paid. Total paid: \$".number_format((float) $this->request->amount_paid, 2).'.',
            default => "Your withdrawal request for investment {$reference} is now {$this->request->status->getLabel()}.",
        };
    }
}
