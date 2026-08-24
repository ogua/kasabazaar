<x-filament-panels::page>
    @php
        $statement = $this->statement();
        $totals = $statement['totals'];
        $money = fn ($amount) => ($amount < 0 ? '-' : '').'$'.number_format(abs((float) $amount), 2);
    @endphp

    <form wire:submit.prevent>
        {{ $this->form }}
    </form>

    @unless ($statement['is_balanced'])
        {{-- Surfaced, never suppressed: an imbalance means the records do not fully
             articulate, and that must be resolved before this reaches a lender. --}}
        <x-filament::section>
            <div class="rounded-lg border border-warning-300 bg-warning-50 p-4 dark:border-warning-700 dark:bg-warning-950/50">
                <p class="font-semibold text-warning-800 dark:text-warning-200">This balance sheet does not balance.</p>
                <p class="mt-1 text-sm text-warning-700 dark:text-warning-300">
                    Total assets differ from liabilities plus equity by {{ $money($statement['imbalance']) }}.
                    @if ($statement['missing_cash_position'] ?? false)
                        <strong>No cash position has been recorded for this year</strong>, so the sheet shows
                        receivables and no bank balance — that is almost certainly the whole gap. Record one
                        under Reports → Cash Positions.
                    @else
                        This usually means an account has not been recorded for the year — most often share
                        capital or brought-forward retained earnings.
                    @endif
                    Resolve it before issuing this statement.
                </p>
            </div>
        </x-filament::section>
    @endunless

    <x-filament::section>
        <x-slot name="heading">As at {{ \Carbon\Carbon::parse($statement['as_of'])->format('F j, Y') }}</x-slot>
        <x-slot name="description">
            Presented in {{ $statement['currency'] }}.
            @if ($statement['source'] === 'manual')
                Entered from the accountant's books.
            @else
                Derived from live records; Cedi amounts translated at
                GHS {{ number_format($statement['exchange_rate'], 4) }} to USD 1.00.
            @endif
        </x-slot>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
            @foreach ([
                ['Total Assets', $totals['total_assets'], 'text-success-600 dark:text-success-400'],
                ['Total Liabilities', $totals['total_liabilities'], 'text-warning-600 dark:text-warning-400'],
                ['Total Equity', $totals['total_equity'], 'text-info-600 dark:text-info-400'],
            ] as [$label, $value, $tone])
                <div class="rounded-lg bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</div>
                    <div class="mt-1 text-xl font-bold tabular-nums {{ $tone }}">{{ $money($value) }}</div>
                </div>
            @endforeach
        </div>

        @include('filament.pages.partials.statement-group', [
            'heading' => 'Current Assets',
            'groups' => $statement['assets']['current'],
            'total' => $totals['current_assets'],
            'totalLabel' => 'Total Current Assets',
        ])

        @include('filament.pages.partials.statement-group', [
            'heading' => 'Non-Current Assets',
            'groups' => $statement['assets']['fixed'],
            'total' => $totals['fixed_assets'],
            'totalLabel' => 'Total Non-Current Assets',
        ])

        @include('filament.pages.partials.statement-group', [
            'heading' => 'Current Liabilities',
            'groups' => $statement['liabilities']['current'],
            'total' => $totals['current_liabilities'],
            'totalLabel' => 'Total Current Liabilities',
        ])

        @include('filament.pages.partials.statement-group', [
            'heading' => 'Non-Current Liabilities',
            'groups' => $statement['liabilities']['long_term'],
            'total' => $totals['long_term_liabilities'],
            'totalLabel' => 'Total Non-Current Liabilities',
        ])

        @include('filament.pages.partials.statement-group', [
            'heading' => 'Equity',
            'groups' => $statement['equity'],
            'total' => $totals['total_equity'],
            'totalLabel' => 'Total Equity',
        ])

        <div class="rounded-lg bg-primary-600 px-4 py-3 text-white">
            <div class="flex items-center justify-between">
                <span class="font-bold">Total Liabilities &amp; Equity</span>
                <span class="text-lg font-bold tabular-nums">{{ $money($totals['liabilities_and_equity']) }}</span>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
