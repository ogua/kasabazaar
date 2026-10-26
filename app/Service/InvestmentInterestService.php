<?php

namespace App\Service;

use App\Enums\InvestmentCapitalType;
use App\Enums\InvestmentTransactionType;
use App\Models\Investment;
use App\Models\InvestmentTransaction;
use App\Models\User;
use App\Notifications\InvestmentInterestPosted;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
     * The last date interest has been posted through, or null when nothing has been posted yet.
     */
    public function postedThrough(Investment $investment): ?Carbon
    {
        if ($investment->last_interest_posted_through) {
            return Carbon::parse($investment->last_interest_posted_through)->startOfDay();
        }

        return $investment->last_interest_posted_year
            ? Carbon::create($investment->last_interest_posted_year, 12, 31)
            : null;
    }

    /**
     * The first day not yet covered by posted interest — the day after the posting
     * cursor, or the start date for an investment that has never had interest posted.
     */
    public function nextUnpostedDate(Investment $investment): Carbon
    {
        $startDate = Carbon::parse($investment->start_date)->startOfDay();
        $postedThrough = $this->postedThrough($investment);

        return $postedThrough ? $postedThrough->copy()->addDay()->max($startDate) : $startDate;
    }

    /**
     * The ledger balance at the start of $date: every posted movement dated before it,
     * plus the principal when the opening deposit was never recorded as a ledger row
     * (tranches created directly in the admin panel).
     */
    public function balanceBefore(Investment $investment, Carbon $date): float
    {
        $hasOpeningRow = InvestmentTransaction::where('investment_id', $investment->id)
            ->where('posted', true)
            ->whereIn('type', [InvestmentTransactionType::contribution->value, InvestmentTransactionType::conversion_in->value])
            ->exists();

        $priorRows = InvestmentTransaction::where('investment_id', $investment->id)
            ->where('posted', true)
            ->where('date', '<', $date->toDateString());

        $net = (float) (clone $priorRows)->sum('credit') - (float) (clone $priorRows)->sum('debit');

        return round(($hasOpeningRow ? 0.0 : (float) $investment->principal_amount) + $net, 2);
    }

    /**
     * Accrue interest from $from to $to off the ledger, one segment per calendar year.
     * Posted capital movements (contributions, withdrawals, conversions, adjustments)
     * change the earning balance from their ledger date, so money withdrawn mid-year
     * stops earning and money added starts earning. Interest is simple within a year at
     * that year's rate (Actual/365, as the signed agreements are written) and compounds
     * at each Dec 31.
     *
     * @return array{segments: list<array{year: int, period_start: string, period_end: string, balance_start: float, average_balance: float, has_movements: bool, days_held: int, rate: float, rate_source: string, interest: float, balance_end: float}>, total_interest: float, balance_end: float}
     */
    public function ledgerAccrual(Investment $investment, Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();

        if ($from->greaterThan($to)) {
            throw new \InvalidArgumentException('Period start must not be after period end.');
        }

        $movementsByDate = InvestmentTransaction::where('investment_id', $investment->id)
            ->where('posted', true)
            ->where('type', '!=', InvestmentTransactionType::interest_credit->value)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->groupBy(fn (InvestmentTransaction $row) => $row->date->toDateString())
            ->map(fn (Collection $rows) => round($rows->sum(fn (InvestmentTransaction $row) => (float) $row->credit - (float) $row->debit), 2));

        $balance = $this->balanceBefore($investment, $from);
        $segments = [];
        $cursor = $from->copy();

        while ($cursor->lessThanOrEqualTo($to)) {
            $segmentEnd = Carbon::create($cursor->year, 12, 31)->min($to);
            $resolved = $this->rateResolver->resolve($investment, $cursor->year);

            $balanceStart = null;
            $balanceDays = 0.0;
            $daysHeld = 0;
            $hasMovements = false;

            for ($day = $cursor->copy(); $day->lessThanOrEqualTo($segmentEnd); $day->addDay()) {
                $movement = $movementsByDate->get($day->toDateString(), 0.0);
                $balance = round($balance + $movement, 2);
                $hasMovements = $hasMovements || ($movement != 0.0 && $balanceStart !== null);
                $balanceStart ??= $balance;
                $balanceDays += $balance;
                $daysHeld++;
            }

            $interest = round($balanceDays * ($resolved['rate'] / 100) / 365, 2);
            $balance = round($balance + $interest, 2);

            $segments[] = [
                'year' => $cursor->year,
                'period_start' => $cursor->toDateString(),
                'period_end' => $segmentEnd->toDateString(),
                'balance_start' => $balanceStart,
                'average_balance' => round($balanceDays / $daysHeld, 2),
                'has_movements' => $hasMovements,
                'days_held' => $daysHeld,
                'rate' => $resolved['rate'],
                'rate_source' => $resolved['source'],
                'interest' => $interest,
                'balance_end' => $balance,
            ];

            $cursor = $segmentEnd->copy()->addDay();
        }

        return [
            'segments' => $segments,
            'total_interest' => round(collect($segments)->sum('interest'), 2),
            'balance_end' => $balance,
        ];
    }

    /**
     * Generate the year-end draft for $year and return its row. See generateYearEndDrafts().
     */
    public function generateDraft(Investment $investment, int $year): InvestmentTransaction
    {
        return $this->generateYearEndDrafts($investment, $year)->last();
    }

    /**
     * Draft the interest an investment has earned by Dec 31 of $year. Accrual runs
     * from the first unposted day to Dec 31, so a tranche opened in November earns its
     * pro-rated days at year end, a year already part-posted (e.g. through Sep 30) gets
     * the remainder, and a year that was missed is caught up instead of lost — one
     * draft row per calendar year. Does not touch investment.current_balance — requires
     * postDraft()/postDraftBatch() to finalize.
     *
     * @return Collection<int, InvestmentTransaction>
     */
    public function generateYearEndDrafts(Investment $investment, int $year): Collection
    {
        $this->assertCompounding($investment);

        $yearEnd = Carbon::create($year, 12, 31);

        if (Carbon::parse($investment->start_date)->greaterThan($yearEnd)) {
            throw new \RuntimeException("Investment {$investment->reference} starts after {$year}; it has no interest for that year.");
        }

        $from = $this->nextUnpostedDate($investment);

        if ($from->greaterThan($yearEnd)) {
            throw new \RuntimeException("Interest for {$year} has already been posted for investment {$investment->reference}.");
        }

        // Regenerate cleanly if stale drafts for these days exist (e.g. rates changed since).
        InvestmentTransaction::where('investment_id', $investment->id)
            ->where('type', InvestmentTransactionType::interest_credit->value)
            ->where('posted', false)
            ->where(fn ($query) => $query->whereNull('period_start')->orWhere('period_start', '<=', $yearEnd->toDateString()))
            ->delete();

        return $this->createDrafts($investment, $from, $yearEnd);
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
        $this->assertCompounding($investment);

        if ($periodStart->greaterThan($periodEnd)) {
            throw new \InvalidArgumentException('Period start must not be after period end.');
        }

        $cursor = $this->postedThrough($investment);

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

        return $this->createDrafts($investment, $periodStart, $periodEnd);
    }

    /**
     * Persist one unposted interest_credit row per calendar-year segment of the ledger
     * accrual between the two dates. Each row's op_balance is the balance just before
     * its credit lands, which postDraft() re-derives from the ledger at posting time.
     *
     * @return Collection<int, InvestmentTransaction>
     */
    protected function createDrafts(Investment $investment, Carbon $from, Carbon $to, ?string $label = null): Collection
    {
        $accrual = $this->ledgerAccrual($investment, $from, $to);

        return collect($accrual['segments'])->map(fn (array $segment) => InvestmentTransaction::create([
            'investment_id' => $investment->id,
            'investor_id' => $investment->investor_id,
            'date' => $segment['period_end'],
            'period_start' => $segment['period_start'],
            'period_end' => $segment['period_end'],
            'rate_applied' => $segment['rate'],
            'type' => InvestmentTransactionType::interest_credit->value,
            'op_balance' => round($segment['balance_end'] - $segment['interest'], 2),
            'credit' => $segment['interest'],
            'year' => $segment['year'],
            'posted' => false,
            'description' => trim(sprintf(
                '%s %s%% (%s) x %d days / 365 on %s%s [%s – %s]',
                $label ?? '',
                number_format($segment['rate'], 2),
                $segment['rate_source'],
                $segment['days_held'],
                $segment['has_movements'] ? 'average balance ' : '',
                number_format($segment['has_movements'] ? $segment['average_balance'] : $segment['balance_start'], 2),
                $segment['period_start'],
                $segment['period_end']
            )),
        ]));
    }

    /**
     * Loans pay simple interest in cash on their payout schedule
     * (InvestmentInterestPayoutService) and must never have interest credited to
     * their balance, which would pay the same interest twice.
     */
    protected function assertCompounding(Investment $investment): void
    {
        if ($investment->capital_type === InvestmentCapitalType::loan) {
            throw new \RuntimeException(
                "{$investment->reference} is a loan — its interest is paid in cash on the payout schedule, never credited to the balance."
            );
        }
    }

    /**
     * Generate a single, un-split draft interest_credit row for an exact date range at a
     * manually supplied rate — unlike generateDraftForPeriod(), this never splits at a
     * Dec 31 boundary or compounds mid-range; the rate applies flat across the whole
     * period. Used when staff need to record interest for a period and rate agreed
     * outside the configured InvestmentRateSetting history. Does not touch
     * investment.current_balance — requires postDraft() to finalize.
     */
    public function generateManualDraft(Investment $investment, Carbon $periodStart, Carbon $periodEnd, float $rate): InvestmentTransaction
    {
        $this->assertCompounding($investment);

        if ($periodStart->greaterThan($periodEnd)) {
            throw new \InvalidArgumentException('Period start must not be after period end.');
        }

        $cursor = $this->postedThrough($investment);

        if ($cursor && $periodStart->lessThanOrEqualTo($cursor)) {
            throw new \RuntimeException(
                "Interest through {$cursor->toDateString()} has already been posted for investment {$investment->reference}."
            );
        }

        // Regenerate cleanly if a stale draft exists.
        InvestmentTransaction::where('investment_id', $investment->id)
            ->where('type', InvestmentTransactionType::interest_credit->value)
            ->where('posted', false)
            ->delete();

        $balanceAtPeriodStart = (float) $investment->current_balance;
        $daysHeld = $periodStart->diffInDays($periodEnd) + 1;
        $interest = round($balanceAtPeriodStart * ($rate / 100) * ($daysHeld / 365), 2);

        return InvestmentTransaction::create([
            'investment_id' => $investment->id,
            'investor_id' => $investment->investor_id,
            'date' => $periodEnd,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'rate_applied' => $rate,
            'type' => InvestmentTransactionType::interest_credit->value,
            'op_balance' => $balanceAtPeriodStart,
            'credit' => $interest,
            'year' => $periodEnd->year,
            'posted' => false,
            'description' => sprintf(
                '%s%% (manual) x %d days / 365 on %s [%s – %s]',
                number_format($rate, 2),
                $daysHeld,
                number_format($balanceAtPeriodStart, 2),
                $periodStart->toDateString(),
                $periodEnd->toDateString()
            ),
        ]);
    }

    /**
     * Finalize a draft interest_credit row, optionally overriding the computed amount,
     * and update the investment's authoritative running balance. The credit lands after
     * everything already on the ledger for its date, and every later row is re-chained
     * onto it — so a withdrawal or contribution recorded since the draft was generated
     * is kept, never overwritten by the draft's stale opening balance.
     */
    public function postDraft(InvestmentTransaction $draft, User $postedBy, ?float $overrideAmount = null): InvestmentTransaction
    {
        $investment = $draft->investment;
        $this->assertCompounding($investment);

        return DB::transaction(function () use ($draft, $postedBy, $overrideAmount, $investment) {
            if ($draft->period_start && $draft->period_end) {
                $overlapping = InvestmentTransaction::where('investment_id', $investment->id)
                    ->where('type', InvestmentTransactionType::interest_credit->value)
                    ->where('posted', true)
                    ->where('credit', '!=', 0)
                    ->where('period_start', '<=', $draft->period_end->toDateString())
                    ->where('period_end', '>=', $draft->period_start->toDateString())
                    ->first();

                if ($overlapping) {
                    throw new \RuntimeException(sprintf(
                        'Interest for %s – %s is already posted on %s; discard this draft instead.',
                        $overlapping->period_start->toDateString(),
                        $overlapping->period_end->toDateString(),
                        $investment->reference
                    ));
                }
            }

            if ($overrideAmount !== null) {
                $draft->credit = round($overrideAmount, 2);
            }

            $draftDate = Carbon::parse($draft->date)->startOfDay();

            $draft->op_balance = $this->balanceBefore($investment, $draftDate->copy()->addDay());
            $draft->posted = true;
            $draft->posted_by = $postedBy->id;
            $draft->posted_at = now();
            $draft->save();

            $running = (float) $draft->cl_balance;

            $later = InvestmentTransaction::where('investment_id', $investment->id)
                ->where('posted', true)
                ->where('date', '>', $draftDate->toDateString())
                ->orderBy('date')
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            foreach ($later as $row) {
                $row->op_balance = $running;
                $row->save();
                $running = (float) $row->cl_balance;
            }

            $postedThrough = $draft->period_end ?? Carbon::create($draft->year, 12, 31);
            $previousCursor = $this->postedThrough($investment);
            $advancesCursor = ! $previousCursor || $postedThrough->greaterThan($previousCursor);

            $investment->update([
                'current_balance' => $running,
                'last_interest_posted_year' => $advancesCursor ? $draft->year : $investment->last_interest_posted_year,
                'last_interest_posted_through' => $advancesCursor ? $postedThrough : $previousCursor,
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
     * Fully reverse a wrongly-posted interest_credit transaction — e.g. the wrong
     * rate or period was posted by mistake. Reuses reviseInterestTransaction()'s
     * cascade (zeroing the credit correctly recomputes every subsequent op_balance/
     * cl_balance and investment.current_balance) rather than hard-deleting the row,
     * which would destroy the audit trail for a posting that already moved the
     * balance. Additionally rolls back the posting cursors if this was the
     * investment's most recently posted year, since reviseInterestTransaction()
     * deliberately leaves them untouched (correct for an amount tweak, wrong for a
     * full reversal) — without this, generateDraft() would refuse to ever
     * regenerate that year.
     */
    public function reverseInterestCredit(InvestmentTransaction $transaction, User $editor, string $reason): InvestmentTransaction
    {
        if ($transaction->type !== InvestmentTransactionType::interest_credit) {
            throw new \InvalidArgumentException('Only interest_credit transactions may be reversed.');
        }

        if (! $transaction->posted) {
            throw new \InvalidArgumentException('This draft has not been posted yet — discard it instead of reversing it.');
        }

        return DB::transaction(function () use ($transaction, $editor, $reason) {
            $this->reviseInterestTransaction($transaction, 0.0, $editor, "REVERSED: {$reason}");

            $transaction->refresh();
            $investment = $transaction->investment;

            if ($investment->last_interest_posted_year === $transaction->year) {
                $priorPosted = InvestmentTransaction::where('investment_id', $investment->id)
                    ->where('type', InvestmentTransactionType::interest_credit->value)
                    ->where('posted', true)
                    ->where('id', '!=', $transaction->id)
                    ->orderByDesc('period_end')
                    ->first();

                $investment->update([
                    'last_interest_posted_year' => $priorPosted?->year,
                    'last_interest_posted_through' => $priorPosted?->period_end,
                ]);
            }

            return $transaction->fresh();
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

        // Accrue from the first unposted day, not Jan 1 of the year after the last
        // posting — a year posted only through Sep 30 still has Oct–Dec to earn.
        $from = $this->nextUnpostedDate($investment);

        if ($from->lessThanOrEqualTo($asOfDate)) {
            $accrual = $this->ledgerAccrual($investment, $from, $asOfDate);
            $balance = $accrual['balance_end'];
            $totalInterest += $accrual['total_interest'];
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
     * entry per posted interest_credit transaction, plus — when $includeUnposted is
     * true — any not-yet-posted trailing segments up to $asOfDate) instead of
     * collapsed into totals. Used to render the itemized interest breakdown on
     * agreement PDFs.
     *
     * When $includeUnposted is false, only actually-posted interest is included:
     * no trailing period is projected, and the returned 'as_of' reflects the date
     * through which interest has actually been posted (or the investment's start
     * date, if nothing has been posted yet) rather than the requested $asOfDate.
     *
     * @return array{principal: float, segments: array, interest_earned_total: float, compounded_balance: float, as_of: Carbon}
     */
    public function segmentedValuationAsOf(Investment $investment, Carbon $asOfDate, bool $includeUnposted = true): array
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
        $lastCoveredDate = $lastPosted ? Carbon::parse($lastPosted->period_end) : Carbon::parse($investment->start_date);

        if ($includeUnposted && $cursor->lessThanOrEqualTo($asOfDate)) {
            $trailing = $this->ledgerAccrual($investment, $cursor, $asOfDate);

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
            $lastCoveredDate = $asOfDate;
        }

        return [
            'principal' => (float) $investment->principal_amount,
            'segments' => $segments,
            'interest_earned_total' => round(collect($segments)->sum('interest'), 2),
            'compounded_balance' => round($balance, 2),
            'as_of' => $includeUnposted ? $asOfDate : $lastCoveredDate,
        ];
    }

    /**
     * True-up every unposted day through $throughDate (e.g. before a full withdrawal
     * payout closes the investment), then immediately post it. Covers whatever is
     * outstanding — the rest of a part-posted year, or a whole prior year whose
     * year-end draft is still awaiting review — and supersedes any pending drafts.
     * Returns the last row posted, or null when nothing was left to post.
     */
    public function postStubPeriodInterest(Investment $investment, Carbon $throughDate, User $postedBy): ?InvestmentTransaction
    {
        $this->assertCompounding($investment);

        $from = $this->nextUnpostedDate($investment);
        $throughDate = $throughDate->copy()->startOfDay();

        if ($from->greaterThan($throughDate)) {
            return null;
        }

        return DB::transaction(function () use ($investment, $from, $throughDate, $postedBy) {
            InvestmentTransaction::where('investment_id', $investment->id)
                ->where('type', InvestmentTransactionType::interest_credit->value)
                ->where('posted', false)
                ->delete();

            $drafts = $this->createDrafts($investment, $from, $throughDate, "Stub-period interest through {$throughDate->toDateString()}:");

            return $this->postDraftBatch($drafts, $postedBy)->last();
        });
    }
}
