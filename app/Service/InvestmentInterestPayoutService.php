<?php

namespace App\Service;

use App\Enums\InvestmentInterestPayoutStatus;
use App\Enums\InvestmentTransactionType;
use App\Models\Investment;
use App\Models\InvestmentInterestPayout;
use App\Models\InvestmentTransaction;
use App\Models\User;
use App\Notifications\InvestmentInterestPayoutStatusUpdated;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Handles periodic cash interest payouts for capital_type=loan investments — a
 * distinct pipeline from InvestmentInterestService because loan interest is always
 * simple interest on the flat principal_amount, paid out in cash, and must never
 * fold into current_balance (unlike compounding interest_credit postings).
 */
class InvestmentInterestPayoutService
{
    public function __construct(protected InvestmentRateResolver $rateResolver) {}

    /**
     * Simple interest for one payout period, computed off the flat principal_amount
     * (never current_balance — a loan's balance does not compound). The rate is
     * resolved once, from the investment's start year, and reused for every period
     * regardless of which calendar year it falls in: loans carry a fixed rate for
     * the life of the term, unlike compounding investments which may re-price
     * annually via company-wide rate settings.
     *
     * @return array{rate: float, source: string, days: int, interest: float}
     */
    public function periodInterest(Investment $investment, Carbon $periodStart, Carbon $periodEnd): array
    {
        $resolved = $this->rateResolver->resolve($investment, Carbon::parse($investment->start_date)->year);
        $days = $periodStart->diffInDays($periodEnd) + 1;
        $interest = round((float) $investment->principal_amount * ($resolved['rate'] / 100) * ($days / 365), 2);

        return [
            'rate' => $resolved['rate'],
            'source' => $resolved['source'],
            'days' => $days,
            'interest' => $interest,
        ];
    }

    public function generateDue(Investment $investment, Carbon $periodStart, Carbon $periodEnd, Carbon $dueDate): InvestmentInterestPayout
    {
        $calc = $this->periodInterest($investment, $periodStart, $periodEnd);

        return InvestmentInterestPayout::create([
            'investment_id' => $investment->id,
            'investor_id' => $investment->investor_id,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'principal_balance' => $investment->principal_amount,
            'rate_applied' => $calc['rate'],
            'amount' => $calc['interest'],
            'status' => InvestmentInterestPayoutStatus::due->value,
        ]);
    }

    /**
     * Project the full payout schedule for a loan from start_date to maturity_date —
     * used both by the agreement PDF (so the schedule shown to the lender matches
     * exactly what will be generated) and available for staff preview. Read-only;
     * does not create any rows.
     *
     * @return array<int, array{period_start: Carbon, period_end: Carbon, due_date: Carbon, rate: float, amount: float}>
     */
    public function projectSchedule(Investment $investment): array
    {
        if (! $investment->payout_frequency || ! $investment->maturity_date) {
            return [];
        }

        $months = $investment->payout_frequency->months();
        $schedule = [];
        $periodStart = Carbon::parse($investment->start_date);
        $periodEnd = $periodStart->copy()->addMonths($months);

        while ($periodEnd->lte($investment->maturity_date)) {
            $calc = $this->periodInterest($investment, $periodStart, $periodEnd);

            $schedule[] = [
                'period_start' => $periodStart->copy(),
                'period_end' => $periodEnd->copy(),
                'due_date' => $periodEnd->copy(),
                'rate' => $calc['rate'],
                'amount' => $calc['interest'],
            ];

            // Advance from periodEnd's own prior value (not periodStart) so due dates
            // stay anchored the same way GenerateInvestmentInterestPayoutDrafts advances
            // next_payout_due_date — the schedule shown here must match what actually
            // gets generated.
            $periodStart = $periodEnd->copy()->addDay();
            $periodEnd = $periodEnd->copy()->addMonths($months);
        }

        return $schedule;
    }

    /**
     * Record an actual cash payment against a due/processing payout. Unlike
     * InvestmentWithdrawalApprovalService::recordPayment(), this never touches
     * investment.current_balance or next_payout_due_date — the balance stays flat
     * for the life of a loan, and the cursor already advanced when this row was
     * generated (see GenerateInvestmentInterestPayoutDrafts).
     */
    public function recordPayment(InvestmentInterestPayout $payout, ?float $amountOverride, User $recordedBy, array $paymentData = []): InvestmentInterestPayout
    {
        return DB::transaction(function () use ($payout, $amountOverride, $recordedBy, $paymentData) {
            $amount = $amountOverride ?? ((float) $payout->amount - (float) $payout->amount_paid);
            $investment = $payout->investment;

            // debit = credit so cl_balance collapses back to op_balance: cash leaves
            // the company but a loan's principal balance never moves for interest.
            InvestmentTransaction::create([
                'investment_id' => $investment->id,
                'investor_id' => $investment->investor_id,
                'date' => now(),
                'type' => InvestmentTransactionType::interest_payout->value,
                'period_start' => $payout->period_start,
                'period_end' => $payout->period_end,
                'rate_applied' => $payout->rate_applied,
                'op_balance' => $investment->principal_amount,
                'debit' => $amount,
                'credit' => $amount,
                'posted' => true,
                'posted_by' => $recordedBy->id,
                'posted_at' => now(),
                'reference_id' => $payout->id,
                'description' => "Interest payout for period {$payout->period_start->format('M j, Y')} – {$payout->period_end->format('M j, Y')}",
            ]);

            $newAmountPaid = round((float) $payout->amount_paid + $amount, 2);
            $isFullyPaid = $newAmountPaid >= (float) $payout->amount - 0.01;

            $payout->update([
                'amount_paid' => $newAmountPaid,
                'paid_at' => $isFullyPaid ? now() : $payout->paid_at,
                'status' => $isFullyPaid ? InvestmentInterestPayoutStatus::paid->value : InvestmentInterestPayoutStatus::processing->value,
                'payout_gateway' => $paymentData['payout_gateway'] ?? $payout->payout_gateway ?? 'manual',
                'payment_method' => $paymentData['payment_method'] ?? $payout->payment_method,
                'payment_reference' => $paymentData['payment_reference'] ?? $payout->payment_reference,
                'receipt_path' => $paymentData['receipt_path'] ?? $payout->receipt_path,
                'reviewed_by' => $recordedBy->id,
                'reviewed_at' => now(),
            ]);

            InvestorNotifier::notify($payout->investor_id, new InvestmentInterestPayoutStatusUpdated($payout->fresh()));

            return $payout->fresh();
        });
    }

    public function markSkipped(InvestmentInterestPayout $payout, User $staff, string $reason): void
    {
        $payout->update([
            'status' => InvestmentInterestPayoutStatus::skipped->value,
            'notes' => trim(($payout->notes ?? '')."\nSkipped by {$staff->name} on ".now()->toDateTimeString().": {$reason}"),
            'reviewed_by' => $staff->id,
            'reviewed_at' => now(),
        ]);

        InvestorNotifier::notify($payout->investor_id, new InvestmentInterestPayoutStatusUpdated($payout->fresh()));
    }

    /**
     * Reverse a payout that already had cash movement (processing/paid) — records an
     * offsetting ledger entry rather than deleting the original, since real money may
     * already have left the company. Does not claw back funds already sent; that is
     * a manual banking matter staff must resolve separately.
     */
    public function reversePayout(InvestmentInterestPayout $payout, User $staff, string $reason): void
    {
        if (! in_array($payout->status, [InvestmentInterestPayoutStatus::processing, InvestmentInterestPayoutStatus::paid], true)) {
            throw new \InvalidArgumentException('Only a processing or paid payout can be reversed. Use "Discard" for a due payout instead.');
        }

        DB::transaction(function () use ($payout, $staff, $reason) {
            $investment = $payout->investment;
            $amount = (float) $payout->amount_paid;

            InvestmentTransaction::create([
                'investment_id' => $investment->id,
                'investor_id' => $investment->investor_id,
                'date' => now(),
                'type' => InvestmentTransactionType::interest_payout->value,
                'period_start' => $payout->period_start,
                'period_end' => $payout->period_end,
                'rate_applied' => $payout->rate_applied,
                'op_balance' => $investment->principal_amount,
                'debit' => -$amount,
                'credit' => -$amount,
                'posted' => true,
                'posted_by' => $staff->id,
                'posted_at' => now(),
                'reference_id' => $payout->id,
                'description' => "Reversal of interest payout for period {$payout->period_start->format('M j, Y')} – {$payout->period_end->format('M j, Y')}: {$reason}",
                'edited_by' => $staff->id,
                'edited_at' => now(),
            ]);

            $payout->update([
                'status' => InvestmentInterestPayoutStatus::reversed->value,
                'notes' => trim(($payout->notes ?? '')."\nReversed by {$staff->name} on ".now()->toDateTimeString().": {$reason}"),
                'reviewed_by' => $staff->id,
                'reviewed_at' => now(),
            ]);

            InvestorNotifier::notify($payout->investor_id, new InvestmentInterestPayoutStatusUpdated($payout->fresh()));
        });
    }
}
