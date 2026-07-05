<?php

namespace App\Notifications;

use App\Models\InvestmentAnnualStatement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AnnualInvestmentStatementReady extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly InvestmentAnnualStatement $statement,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => "Your {$this->statement->year} Investment Statement is Ready",
            'body' => "Your annual investment statement for {$this->statement->year} has been generated and emailed to you.",
            'type' => 'investment_annual_statement_ready',
            'statement_id' => $this->statement->id,
            'year' => $this->statement->year,
        ];
    }
}
