<?php

namespace App\Service;

use App\Enums\InvestmentTransactionType;
use App\Models\Investment;
use App\Models\InvestmentAnnualStatement;
use App\Notifications\AnnualInvestmentStatementReady;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class AnnualInvestmentStatementService
{
    public static function generatePdf(InvestmentAnnualStatement $statement): \Barryvdh\DomPDF\PDF
    {
        $statement->load('investor.investments.conversionSources.conversion');
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

        // Deliberately NOT filtered with excludingConverted(): a tranche converted
        // during the statement year was live for part of it and must appear in that
        // year's statement. Only tranches already settled before the year opened are
        // dropped — they contributed nothing to it.
        $yearStart = Carbon::create($statement->year, 1, 1);

        $investments = $statement->investor->investments->reject(
            fn (Investment $investment) => $investment->isConverted()
                && $investment->conversionSources
                    ->every(fn ($source) => $source->conversion?->conversion_date?->lt($yearStart) ?? false)
        );

        foreach ($investments as $investment) {
            $yearTxns = $investment->transactions()
                ->where('type', InvestmentTransactionType::interest_credit->value)
                ->where('year', $statement->year)
                ->where('posted', true)
                ->orderBy('date')
                ->get();

            $rows[] = [
                'reference' => $investment->reference,
                'opening_balance' => (float) ($yearTxns->first()->op_balance ?? $investment->principal_amount),
                'interest_credited' => (float) $yearTxns->sum('credit'),
                'closing_balance' => (float) ($yearTxns->last()->cl_balance ?? $investment->current_balance),
            ];
        }

        return $rows;
    }
}
