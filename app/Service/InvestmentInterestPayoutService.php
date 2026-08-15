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
     * Interest is a flat share of the annual amount (principal × rate ÷ payments per
     * year) rather than day-counted — this matches how loan agreements are normally
     * written (e.g. a 9% quarterly loan pays exactly principal × 9% ÷ 4 every quarter)
     * even though the final period may span fewer days than a full period when clamped
     * to the maturity date.
     *
     * @return array{rate: float, source: string, days: int, interest: float}
     */
    public function periodInterest(Investment $investment, Carbon $periodStart, Carbon $periodEnd): array
    {
        $resolved = $this->rateResolver->resolve($investment, Carbon::parse($investment->start_date)->year);
        $days = $periodStart->diffInDays($periodEnd) + 1;
        $paymentsPerYear = 12 / $investment->payout_frequency->months();
        $interest = round((float) $investment->principal_amount * ($resolved['rate'] / 100) / $paymentsPerYear, 2);

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
        $maturityDate = Carbon::parse($investment->maturity_date);
        $startDate = Carbon::parse($investment->start_date);
        $schedule = [];
        $period = 1;

        while (true) {
            // Every period is anchored directly to start_date (start_date + N months),
            // not chained off the previous period's end — so due dates always land on
            // the same day-of-month as the start date (e.g. the 15th of every quarter),
            // matching how a physical loan agreement is normally dated. Consecutive
            // periods share their boundary date (period 1 ends May 15, period 2 begins
            // May 15) rather than starting the day after, since interest is now a flat
            // per-period share rather than day-counted.
            $periodStart = $startDate->copy()->addMonths(($period - 1) * $months);
            $naturalDueDate = $startDate->copy()->addMonths($period * $months);

            // Clamp the final period to the true maturity date rather than overshooting
            // it — this keeps the schedule aligned whether maturity_date lands exactly
            // on a period boundary (the common case) or was manually overridden to match
            // an already-signed physical agreement (e.g. "day before anniversary").
            $isFinalPeriod = $naturalDueDate->gte($maturityDate);
            $dueDate = $isFinalPeriod ? $maturityDate->copy() : $naturalDueDate;

            $calc = $this->periodInterest($investment, $periodStart, $dueDate);

            $schedule[] = [
                'period_start' => $periodStart,
                'period_end' => $dueDate->copy(),
                'due_date' => $dueDate->copy(),
                'rate' => $calc['rate'],
                'amount' => $calc['interest'],
            ];

            if ($isFinalPeriod) {
                break;
            }

            $period++;
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
