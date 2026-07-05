<?php

namespace App\Notifications;

use App\Models\InvestmentTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InvestmentInterestPosted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly InvestmentTransaction $transaction,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $investment = $this->transaction->investment;

        return [
            'title' => 'Interest Posted',
            'body' => "Interest of \${$this->formattedAmount()} for {$this->transaction->year} has been credited to investment {$investment->reference}. New balance: \${$this->formattedBalance()}.",
            'type' => 'investment_interest_posted',
            'investment_id' => $investment->id,
            'transaction_id' => $this->transaction->id,
            'year' => $this->transaction->year,
            'amount' => (float) $this->transaction->credit,
            'new_balance' => (float) $this->transaction->cl_balance,
        ];
    }

    private function formattedAmount(): string
    {
        return number_format((float) $this->transaction->credit, 2);
    }

    private function formattedBalance(): string
    {
        return number_format((float) $this->transaction->cl_balance, 2);
    }
}
