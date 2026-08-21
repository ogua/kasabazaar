<x-filament-panels::page>
    @php
        $statement = $this->statement();
        $totals = $statement['totals'];
        $money = fn ($amount) => ($amount < 0 ? '-' : '').'$'.number_format(abs((float) $amount), 2);
    @endphp

    <form wire:submit.prevent>
        {{ $this->form }}
    </form>

    <x-filament::section>
        <x-slot name="heading">{{ $statement['period']['label'] }}</x-slot>
        <x-slot name="description">
            Presented in {{ $statement['currency'] }}.
            @if ($statement['source'] === 'manual')
                Figures were entered from the accounts prepared by the company's accountant — this
                system does not hold the underlying transactions for {{ $statement['year'] }}.
            @else
                Derived from cashbook, shipment, expense, payroll and investor records. Cedi amounts
                translated at GHS {{ number_format($statement['exchange_rate'], 4) }} to USD 1.00.
            @endif
        </x-slot>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 mb-6">
            @foreach ([
                ['Revenue', $totals['revenue'], 'text-success-600 dark:text-success-400'],
                ['Gross Profit', $totals['gross_profit'], 'text-info-600 dark:text-info-400'],
                ['Operating Profit', $totals['operating_profit'], 'text-info-600 dark:text-info-400'],
                ['Profit for the Year', $totals['net_profit'], $totals['net_profit'] >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400'],
            ] as [$label, $value, $tone])
                <div class="rounded-lg bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</div>
                    <div class="mt-1 text-xl font-bold tabular-nums {{ $tone }}">{{ $money($value) }}</div>
                </div>
            @endforeach
        </div>

        @include('filament.pages.partials.statement-group', [
            'heading' => 'Revenue',
            'groups' => $statement['revenue'],
            'total' => $totals['revenue'],
            'totalLabel' => 'Total Revenue',
        ])

        @include('filament.pages.partials.statement-group', [
            'heading' => 'Cost of Sales',
            'groups' => $statement['cost_of_sales'],
            'total' => $totals['cost_of_sales'],
            'totalLabel' => 'Total Cost of Sales',
        ])

        @include('filament.pages.partials.statement-group', [
            'heading' => 'Operating Expenses',
            'groups' => $statement['operating_expenses'],
            'total' => $totals['operating_expenses'],
            'totalLabel' => 'Total Operating Expenses',
        ])

        {{-- Shown as its own caption rather than folded into operating expenses: this is
             the cost of the investor capital carried on the balance sheet. --}}
        @include('filament.pages.partials.statement-group', [
            'heading' => 'Finance Costs (cost of investor capital)',
            'groups' => $statement['finance_costs'],
            'total' => $totals['finance_costs'],
            'totalLabel' => 'Total Finance Costs',
        ])

        <div class="rounded-lg bg-primary-600 px-4 py-3 text-white">
            <div class="flex items-center justify-between">
                <span class="font-bold">Profit for the Year</span>
                <span class="text-lg font-bold tabular-nums">{{ $money($totals['net_profit']) }}</span>
            </div>
            <div class="mt-1 text-xs opacity-80">
                Gross margin {{ number_format($totals['gross_margin_percentage'], 2) }}% ·
                Net margin {{ number_format($totals['net_margin_percentage'], 2) }}%
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
