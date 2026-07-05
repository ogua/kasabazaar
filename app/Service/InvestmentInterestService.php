<?php

namespace App\Service;

use App\Enums\InvestmentTransactionType;
use App\Models\Investment;
use App\Models\InvestmentTransaction;
use App\Models\User;
use App\Notifications\InvestmentInterestPosted;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InvestmentInterestService
{
    public function __construct(protected InvestmentRateResolver $rateResolver) {}

    /**
     * Compute the interest accrued for a single calendar year, without persisting anything.
     *
     * @return array{year: int, balance_start: float, days_held: int, rate: float, rate_source: string, interest: float, balance_end: float}
     */
    public function yearAccrual(Investment $investment, int $year, float $balanceAtStartOfYear, ?Carbon $capDate = null): array
    {
        $startDate = $investment->start_date instanceof Carbon
            ? $investment->start_date
            : Carbon::parse($investment->start_date);

        $yearStart = Carbon::create($year, 1, 1)->max($startDate);
        $yearEnd = Carbon::create($year, 12, 31);

        if ($capDate) {
            $yearEnd = $yearEnd->min($capDate);
        }

        $daysHeld = $yearStart->greaterThan($yearEnd) ? 0 : $yearStart->diffInDays($yearEnd) + 1;

        $rate = 0.0;
        $rateSource = 'none';

        if ($daysHeld > 0) {
            $resolved = $this->rateResolver->resolve($investment, $year);
            $rate = $resolved['rate'];
            $rateSource = $resolved['source'];
        }

        $interest = round($balanceAtStartOfYear * ($rate / 100) * ($daysHeld / 365), 2);

        return [
            'year' => $year,
            'balance_start' => $balanceAtStartOfYear,
            'days_held' => $daysHeld,
            'rate' => $rate,
            'rate_source' => $rateSource,
            'interest' => $interest,
            'balance_end' => round($balanceAtStartOfYear + $interest, 2),
        ];
    }

    /**
     * Compute (without persisting) the accrual for a year, using the same
     * "latest posted transaction before Jan 1" baseline rule as generateDraft().
     */
    public function previewAccrual(Investment $investment, int $year): array
    {
        $baselineTxn = InvestmentTransaction::where('investment_id', $investment->id)
            ->where('posted', true)
            ->where('date', '<', Carbon::create($year, 1, 1))
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->first();

        $balanceAtStartOfYear = $baselineTxn ? (float) $baselineTxn->cl_balance : (float) $investment->principal_amount;

        return $this->yearAccrual($investment, $year, $balanceAtStartOfYear);
    }

    /**
     * Generate (or regenerate) a draft interest_credit ledger row for a given year.
     * Does not touch investment.current_balance — requires postDraft() to finalize.
     */
    public function generateDraft(Investment $investment, int $year): InvestmentTransaction
    {
        $alreadyPosted = InvestmentTransaction::where('investment_id', $investment->id)
            ->where('type', InvestmentTransactionType::interest_credit->value)
            ->where('year', $year)
            ->where('posted', true)
            ->exists();

        if ($alreadyPosted || ($investment->last_interest_posted_year && $investment->last_interest_posted_year >= $year)) {
            throw new \RuntimeException("Interest for {$year} has already been posted for investment {$investment->reference}.");
        }

        // Regenerate cleanly if a stale draft exists (e.g. rates changed since it was last generated).
        InvestmentTransaction::where('investment_id', $investment->id)
            ->where('type', InvestmentTransactionType::interest_credit->value)
            ->where('year', $year)
            ->where('posted', false)
            ->delete();

        $accrual = $this->previewAccrual($investment, $year);
        $balanceAtStartOfYear = $accrual['balance_start'];

        return InvestmentTransaction::create([
            'investment_id' => $investment->id,
            'investor_id' => $investment->investor_id,
            'date' => Carbon::create($year, 12, 31),
            'type' => InvestmentTransactionType::interest_credit->value,
            'op_balance' => $balanceAtStartOfYear,
            'credit' => $accrual['interest'],
            'year' => $year,
            'posted' => false,
            'description' => sprintf(
                '%s%% (%s) x %d days / 365 on %s',
                number_format($accrual['rate'], 2),
                $accrual['rate_source'],
                $accrual['days_held'],
                number_format($balanceAtStartOfYear, 2)
            ),
        ]);
    }

    /**
     * Finalize a draft interest_credit row, optionally overriding the computed amount,
     * and update the investment's authoritative running balance.
     */
    public function postDraft(InvestmentTransaction $draft, User $postedBy, ?float $overrideAmount = null): InvestmentTransaction
    {
        return DB::transaction(function () use ($draft, $postedBy, $overrideAmount) {
            if ($overrideAmount !== null) {
                $draft->credit = round($overrideAmount, 2);
            }

            $draft->posted = true;
            $draft->posted_by = $postedBy->id;
            $draft->posted_at = now();
            $draft->save();

            $draft->investment->update([
                'current_balance' => $draft->cl_balance,
                'last_interest_posted_year' => $draft->year,
            ]);

            InvestorNotifier::notify($draft->investor_id, new InvestmentInterestPosted($draft->fresh()));

            return $draft->fresh();
        });
    }

    /**
     * Live valuation (principal + posted interest + on-the-fly accrual for any
     * not-yet-posted year), as of an arbitrary date. Used by PDFs and payout calc.
     *
     * @return array{principal: float, interest_earned_total: float, compounded_balance: float, as_of: Carbon}
     */
    public function valuationAsOf(Investment $investment, Carbon $asOfDate): array
    {
        $totalInterest = (float) InvestmentTransaction::where('investment_id', $investment->id)
            ->where('type', InvestmentTransactionType::interest_credit->value)
            ->where('posted', true)
            ->sum('credit');

        $balance = (float) $investment->current_balance;

        $fromYear = $investment->last_interest_posted_year
            ? $investment->last_interest_posted_year + 1
            : (int) Carbon::parse($investment->start_date)->year;

        for ($year = $fromYear; $year <= $asOfDate->year; $year++) {
            $capDate = $year === $asOfDate->year ? $asOfDate : null;
            $accrual = $this->yearAccrual($investment, $year, $balance, $capDate);
            $balance = $accrual['balance_end'];
            $totalInterest += $accrual['interest'];
        }

        return [
            'principal' => (float) $investment->principal_amount,
            'interest_earned_total' => round($totalInterest, 2),
            'compounded_balance' => round($balance, 2),
            'as_of' => $asOfDate,
        ];
    }

    /**
     * True-up interest for a partial year ending at $throughDate (e.g. before a full
     * withdrawal payout closes the investment), then immediately post it.
     */
    public function postStubPeriodInterest(Investment $investment, Carbon $throughDate, User $postedBy): InvestmentTransaction
    {
        $year = $throughDate->year;

        $alreadyPosted = InvestmentTransaction::where('investment_id', $investment->id)
            ->where('type', InvestmentTransactionType::interest_credit->value)
            ->where('year', $year)
            ->where('posted', true)
            ->first();

        if ($alreadyPosted) {
            return $alreadyPosted;
        }

        $balanceAtStartOfYear = (float) $investment->current_balance;
        $accrual = $this->yearAccrual($investment, $year, $balanceAtStartOfYear, $throughDate);

        $draft = InvestmentTransaction::create([
            'investment_id' => $investment->id,
            'investor_id' => $investment->investor_id,
            'date' => $throughDate,
            'type' => InvestmentTransactionType::interest_credit->value,
            'op_balance' => $balanceAtStartOfYear,
            'credit' => $accrual['interest'],
            'year' => $year,
            'posted' => false,
            'description' => sprintf(
                'Stub-period interest through %s (%d days at %s%%, %s)',
                $throughDate->toDateString(),
                $accrual['days_held'],
                number_format($accrual['rate'], 2),
                $accrual['rate_source']
            ),
        ]);

        return $this->postDraft($draft, $postedBy);
    }
}
