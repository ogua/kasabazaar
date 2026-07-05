<?php

namespace App\Service;

use App\Enums\InvestmentTransactionType;
use App\Models\InvestmentAnnualStatement;
use App\Notifications\AnnualInvestmentStatementReady;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;

class AnnualInvestmentStatementService
{
    public static function generatePdf(InvestmentAnnualStatement $statement): \Barryvdh\DomPDF\PDF
    {
        $statement->load('investor.investments');
        $rows = self::buildRows($statement);

        return Pdf::loadView('reports.investment-annual-statement-pdf', [
            'statement' => $statement,
            'investor' => $statement->investor,
            'rows' => $rows,
            'totalValue' => collect($rows)->sum('closing_balance'),
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);
    }

    public static function downloadPdf(InvestmentAnnualStatement $statement)
    {
        return self::generatePdf($statement)->download("investment-statement-{$statement->investor->name}-{$statement->year}.pdf");
    }

    public static function streamPdf(InvestmentAnnualStatement $statement)
    {
        return self::generatePdf($statement)->stream("investment-statement-{$statement->investor->name}-{$statement->year}.pdf");
    }

    public static function sendEmail(InvestmentAnnualStatement $statement): bool
    {
        $statement->load('investor');
        $email = $statement->investor?->email;

        if (! $email) {
            return false;
        }

        $pdf = self::generatePdf($statement);

        try {
            Mail::send('emails.investment-annual-statement', [
                'statement' => $statement,
                'investor' => $statement->investor,
            ], function ($message) use ($email, $statement, $pdf) {
                $message->to($email)
                    ->subject("Your {$statement->year} Investment Statement")
                    ->attachData($pdf->output(), "investment-statement-{$statement->year}.pdf", [
                        'mime' => 'application/pdf',
                    ]);
            });

            $statement->update(['sent_at' => now()]);

            InvestorNotifier::notify($statement->investor_id, new AnnualInvestmentStatementReady($statement->fresh()));

            return true;
        } catch (\Exception $e) {
            logger()->error("Failed to send annual investment statement email: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * @return array<int, array{reference: string, opening_balance: float, interest_credited: float, closing_balance: float}>
     */
    private static function buildRows(InvestmentAnnualStatement $statement): array
    {
        $rows = [];

        foreach ($statement->investor->investments as $investment) {
            $interestTxn = $investment->transactions()
                ->where('type', InvestmentTransactionType::interest_credit->value)
                ->where('year', $statement->year)
                ->where('posted', true)
                ->first();

            $rows[] = [
                'reference' => $investment->reference,
                'opening_balance' => (float) ($interestTxn->op_balance ?? $investment->principal_amount),
                'interest_credited' => (float) ($interestTxn->credit ?? 0),
                'closing_balance' => (float) ($interestTxn->cl_balance ?? $investment->current_balance),
            ];
        }

        return $rows;
    }
}
