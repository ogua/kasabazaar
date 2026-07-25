<?php

namespace App\Service;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Investment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\InvestmentTransaction;
use App\Enums\InvestmentTransactionType;
use App\Notifications\InvestmentInterestPosted;

class InvestmentInterestService
{
    public function __construct(protected InvestmentRateResolver $rateResolver) {}

    /**
     * Compute the interest accrued for a single calendar-year segment (never crosses
     * a Dec 31 boundary), without persisting anything.
     *
     * @return array{year: int, period_start: string, period_end: string, balance_start: float, days_held: int, rate: float, rate_source: string, interest: float, balance_end: float}
     */
    protected function segmentAccrual(Investment $investment, Carbon $segStart, Carbon $segEnd, float $balanceStart): array
    {
        $year = $segStart->year;
        $daysHeld = $segStart->greaterThan($segEnd) ? 0 : $segStart->diffInDays($segEnd) + 1;

        $rate = 0.0;
        $rateSource = 'none';

        if ($daysHeld > 0) {
            $resolved = $this->rateResolver->resolve($investment, $year);
            $rate = $resolved['rate'];
            $rateSource = $resolved['source'];
        }

        $interest = round($balanceStart * ($rate / 100) * ($daysHeld / 365), 2);

        return [
            'year' => $year,
            'period_start' => $segStart->toDateString(),
            'period_end' => $segEnd->toDateString(),
            'balance_start' => $balanceStart,
            'days_held' => $daysHeld,
            'rate' => $rate,
            'rate_source' => $rateSource,
            'interest' => $interest,
            'balance_end' => round($balanceStart + $interest, 2),
        ];
    }

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

        $segment = $this->segmentAccrual($investment, $yearStart, $yearEnd, $balanceAtStartOfYear);

        return [
            'year' => $segment['year'],
            'balance_start' => $segment['balance_start'],
            'days_held' => $segment['days_held'],
            'rate' => $segment['rate'],
            'rate_source' => $segment['rate_source'],
            'interest' => $segment['interest'],
            'balance_end' => $segment['balance_end'],
        ];
    }

    /**
     * Compute (without persisting) the accrual for an arbitrary date range, splitting
     * at every Dec 31 boundary the range crosses. Each calendar-year segment resolves
     * its own year's rate and compounds sequentially into the next segment's balance.
     *
     * @return array{period_start: string, period_end: string, segments: array, total_interest: float, balance_end: float}
     */
    public function periodAccrual(Investment $investment, Carbon $periodStart, Carbon $periodEnd, float $balanceAtPeriodStart): array
    {
        if ($periodStart->greaterThan($periodEnd)) {
            throw new \InvalidArgumentException('Period start must not be after period end.');
        }

        $segments = [];
        $balance = $balanceAtPeriodStart;
        $cursor = $periodStart->copy();

        while ($cursor->lessThanOrEqualTo($periodEnd)) {
            $yearEnd = Carbon::create($cursor->year, 12, 31)->min($periodEnd);

            $segment = $this->segmentAccrual($investment, $cursor->copy(), $yearEnd, $balance);
            $segments[] = $segment;
            $balance = $segment['balance_end'];

            $cursor = $yearEnd->copy()->addDay();
        }

        return [
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'segments' => $segments,
            'total_interest' => round(collect($segments)->sum('interest'), 2),
            'balance_end' => round($balance, 2),
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

        // If a partial-year period (e.g. a quarterly posting) has already been posted
        // for this year via generateDraftForPeriod(), a full-calendar-year post here
        // would double-count the days already covered — use the custom period action instead.
        $partialYearPosted = InvestmentTransaction::where('investment_id', $investment->id)
            ->where('type', InvestmentTransactionType::interest_credit->value)
            ->where('year', $year)
            ->where('posted', true)
            ->where('period_end', '<', Carbon::create($year, 12, 31))
            ->first();

        if ($partialYearPosted) {
            throw new \RuntimeException(
                "Partial-year interest already posted for {$year} through {$partialYearPosted->period_end->toDateString()}; use the custom period posting action to post the remainder."
            );
        }

        // Regenerate cleanly if a stale draft exists (e.g. rates changed since it was last generated).
        InvestmentTransaction::where('investment_id', $investment->id)
            ->where('type', InvestmentTransactionType::interest_credit->value)
            ->where('year', $year)
            ->where('posted', false)
            ->delete();

        $accrual = $this->previewAccrual($investment, $year);
        $balanceAtStartOfYear = $accrual['balance_start'];

        $startDate = Carbon::parse($investment->start_date);
        $periodStart = Carbon::create($year, 1, 1)->max($startDate);
        $periodEnd = Carbon::create($year, 12, 31);

        $rate = ($partialYearPosted->rate_applied > 0)
        ? $partialYearPosted->rate_applied
        : $accrual['rate'];

        return InvestmentTransaction::create([
            'investment_id' => $investment->id,
            'investor_id' => $investment->investor_id,
            'date' => $periodEnd,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'rate_applied' => $rate,
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
     * Generate (or regenerate) draft interest_credit rows for an arbitrary date range,
     * one row per calendar-year segment the range crosses. Does not touch
     * investment.current_balance — requires postDraftBatch() to finalize.
     *
     * @return Collection<int, InvestmentTransaction>
     */
    public function generateDraftForPeriod(Investment $investment, Carbon $periodStart, Carbon $periodEnd): Collection
    {
        if ($periodStart->greaterThan($periodEnd)) {
            throw new \InvalidArgumentException('Period start must not be after period end.');
        }

        $cursor = $investment->last_interest_posted_through
            ? Carbon::parse($investment->last_interest_posted_through)
            : ($investment->last_interest_posted_year
                ? Carbon::create($investment->last_interest_posted_year, 12, 31)
                : null);

        if ($cursor && $periodStart->lessThanOrEqualTo($cursor)) {
            throw new \RuntimeException(
                "Interest through {$cursor->toDateString()} has already been posted for investment {$investment->reference}."
            );
        }

        // Regenerate cleanly if stale drafts exist.
        InvestmentTransaction::where('investment_id', $investment->id)
            ->where('type', InvestmentTransactionType::interest_credit->value)
            ->where('posted', false)
            ->delete();

        // The authoritative running balance — deliberately not the "latest posted
        // transaction before Jan 1" baseline that previewAccrual()/generateDraft() use,
        // since a custom period may start mid-year with no year-boundary reference point.
        $balanceAtPeriodStart = (float) $investment->current_balance;

        $accrual = $this->periodAccrual($investment, $periodStart, $periodEnd, $balanceAtPeriodStart);

        $drafts = collect();
        $opBalance = $balanceAtPeriodStart;

        foreach ($accrual['segments'] as $segment) {
            if ($segment['days_held'] <= 0) {
                continue;
            }

            $drafts->push(InvestmentTransaction::create([
                'investment_id' => $investment->id,
                'investor_id' => $investment->investor_id,
                'date' => $segment['period_end'],
                'period_start' => $segment['period_start'],
                'period_end' => $segment['period_end'],
                'rate_applied' => $segment['rate'],
                'type' => InvestmentTransactionType::interest_credit->value,
                'op_balance' => $opBalance,
                'credit' => $segment['interest'],
                'year' => $segment['year'],
                'posted' => false,
                'description' => sprintf(
                    '%s%% (%s) x %d days / 365 on %s [%s – %s]',
                    number_format($segment['rate'], 2),
                    $segment['rate_source'],
                    $segment['days_held'],
                    number_format($segment['balance_start'], 2),
                    $segment['period_start'],
                    $segment['period_end']
                ),
            ]));

            $opBalance = $segment['balance_end'];
        }

        return $drafts;
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
                'last_interest_posted_through' => $draft->period_end ?? Carbon::create($draft->year, 12, 31),
            ]);

            InvestorNotifier::notify($draft->investor_id, new InvestmentInterestPosted($draft->fresh()));

            return $draft->fresh();
        });
    }

    /**
     * Finalize a batch of draft rows (e.g. from generateDraftForPeriod()) in
     * chronological order within a single transaction. $overrideAmounts is keyed
     * by draft id and only sensible for single-segment batches.
     *
     * @param  Collection<int, InvestmentTransaction>  $drafts
     * @param  array<string, float>  $overrideAmounts
     * @return Collection<int, InvestmentTransaction>
     */
    public function postDraftBatch(Collection $drafts, User $postedBy, array $overrideAmounts = []): Collection
    {
        return DB::transaction(function () use ($drafts, $postedBy, $overrideAmounts) {
            return $drafts
                ->sortBy('period_end')
                ->map(fn (InvestmentTransaction $draft) => $this->postDraft($draft, $postedBy, $overrideAmounts[$draft->id] ?? null))
                ->values();
        });
    }

    /**
     * Revise the amount of an already-posted interest_credit transaction, then cascade
     * recalculation through every later transaction and the investment's current
     * balance — mirroring CashbookEntry::rebalanceAfter()'s convention in this app.
     * Only the interest_credit type may be revised; posting cursors are untouched.
     */
    public function reviseInterestTransaction(InvestmentTransaction $transaction, float $newCreditAmount, User $editor, ?string $reason = null): InvestmentTransaction
    {
        if ($transaction->type !== InvestmentTransactionType::interest_credit) {
            throw new \InvalidArgumentException('Only interest_credit transactions may be revised.');
        }

        return DB::transaction(function () use ($transaction, $newCreditAmount, $editor, $reason) {
            $pivot = InvestmentTransaction::where('id', $transaction->id)->lockForUpdate()->firstOrFail();
            $oldAmount = (float) $pivot->credit;

            $pivot->credit = round($newCreditAmount, 2);
            $pivot->description = trim(sprintf(
                '%s [Revised from $%s to $%s by %s on %s%s]',
                $pivot->description ?? '',
                number_format($oldAmount, 2),
                number_format($newCreditAmount, 2),
                $editor->name,
                now()->toDateTimeString(),
                $reason ? "; reason: {$reason}" : ''
            ));
            $pivot->edited_by = $editor->id;
            $pivot->edited_at = now();
            $pivot->save();

            $subsequent = InvestmentTransaction::where('investment_id', $pivot->investment_id)
                ->where(function ($query) use ($pivot) {
                    $query->where('date', '>', $pivot->date)
                        ->orWhere(function ($tieBreak) use ($pivot) {
                            $tieBreak->where('date', '=', $pivot->date)->where('id', '>', $pivot->id);
                        });
                })
                ->orderBy('date')
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            $running = (float) $pivot->cl_balance;

            foreach ($subsequent as $row) {
                $row->op_balance = $running;
                $row->save();
                $running = (float) $row->cl_balance;
            }

            $pivot->investment->update(['current_balance' => $running]);

            return $pivot->fresh();
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
     * Same valuation as valuationAsOf(), but broken down segment-by-segment (one
     * entry per posted interest_credit transaction, plus any not-yet-posted trailing
     * segments up to $asOfDate) instead of collapsed into totals. Used to render the
     * itemized interest breakdown on agreement PDFs.
     *
     * @return array{principal: float, segments: array, interest_earned_total: float, compounded_balance: float, as_of: Carbon}
     */
    public function segmentedValuationAsOf(Investment $investment, Carbon $asOfDate): array
    {
        $postedTxns = InvestmentTransaction::where('investment_id', $investment->id)
            ->where('type', InvestmentTransactionType::interest_credit->value)
            ->where('posted', true)
            ->orderBy('period_end')
            ->get();

        $segments = $postedTxns->map(fn (InvestmentTransaction $txn) => [
            'year' => $txn->year,
            'period_start' => Carbon::parse($txn->period_start),
            'period_end' => Carbon::parse($txn->period_end),
            'days_held' => Carbon::parse($txn->period_start)->diffInDays(Carbon::parse($txn->period_end)) + 1,
            'rate' => (float) $txn->rate_applied,
            'balance_start' => (float) $txn->op_balance,
            'interest' => (float) $txn->credit,
            'balance_end' => (float) $txn->cl_balance,
        ])->all();

        $lastPosted = $postedTxns->last();
        $balance = $lastPosted ? (float) $lastPosted->cl_balance : (float) $investment->principal_amount;
        $cursor = $lastPosted
            ? Carbon::parse($lastPosted->period_end)->addDay()
            : Carbon::parse($investment->start_date);

        if ($cursor->lessThanOrEqualTo($asOfDate)) {
            $trailing = $this->periodAccrual($investment, $cursor, $asOfDate, $balance);

            foreach ($trailing['segments'] as $segment) {
                if ($segment['days_held'] <= 0) {
                    continue;
                }

                $segments[] = [
                    'year' => $segment['year'],
                    'period_start' => Carbon::parse($segment['period_start']),
                    'period_end' => Carbon::parse($segment['period_end']),
                    'days_held' => $segment['days_held'],
                    'rate' => $segment['rate'],
                    'balance_start' => $segment['balance_start'],
                    'interest' => $segment['interest'],
                    'balance_end' => $segment['balance_end'],
                ];
            }

            $balance = $trailing['balance_end'];
        }

        return [
            'principal' => (float) $investment->principal_amount,
            'segments' => $segments,
            'interest_earned_total' => round(collect($segments)->sum('interest'), 2),
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

        $cursor = $investment->last_interest_posted_through
            ? Carbon::parse($investment->last_interest_posted_through)->addDay()
            : Carbon::create($year, 1, 1)->max(Carbon::parse($investment->start_date));

        $draft = InvestmentTransaction::create([
            'investment_id' => $investment->id,
            'investor_id' => $investment->investor_id,
            'date' => $throughDate,
            'period_start' => $cursor,
            'period_end' => $throughDate,
            'rate_applied' => $accrual['rate'],
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
