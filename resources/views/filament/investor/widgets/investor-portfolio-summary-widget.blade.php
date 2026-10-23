<x-filament-widgets::widget>
    <div class="space-y-5">
        {{-- Headline figures --}}
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-emerald-200 bg-white p-5 shadow-sm dark:border-emerald-800 dark:bg-gray-900">
                <div class="flex items-center gap-2 text-sm font-medium text-emerald-700 dark:text-emerald-400">
                    <x-filament::icon icon="heroicon-m-banknotes" class="h-5 w-5" />
                    Total Principal Invested
                </div>
                <div class="mt-2 text-3xl font-bold text-emerald-900 dark:text-emerald-100">${{ number_format($summary['total_principal'], 2) }}</div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">The capital you put in.</p>
            </div>

            <div class="rounded-xl border border-emerald-200 bg-white p-5 shadow-sm dark:border-emerald-800 dark:bg-gray-900">
                <div class="flex items-center gap-2 text-sm font-medium text-emerald-700 dark:text-emerald-400">
                    <x-filament::icon icon="heroicon-m-chart-bar" class="h-5 w-5" />
                    Current Portfolio Value
                </div>
                <div class="mt-2 text-3xl font-bold text-emerald-900 dark:text-emerald-100">${{ number_format($summary['current_portfolio_value'], 2) }}</div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">What the company holds for you today.</p>
            </div>

            <div class="rounded-xl border border-emerald-200 bg-white p-5 shadow-sm dark:border-emerald-800 dark:bg-gray-900">
                <div class="flex items-center gap-2 text-sm font-medium text-emerald-700 dark:text-emerald-400">
                    <x-filament::icon icon="heroicon-m-arrow-trending-up" class="h-5 w-5" />
                    Total Interest Earned
                </div>
                <div @class([
                    'mt-2 text-3xl font-bold',
                    'text-emerald-900 dark:text-emerald-100' => $summary['total_interest_earned'] >= 0,
                    'text-red-700 dark:text-red-400' => $summary['total_interest_earned'] < 0,
                ])>${{ number_format($summary['total_interest_earned'], 2) }}</div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Your returns to date, paid or accrued.</p>
            </div>
        </div>

        {{-- Secondary facts --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-lg bg-emerald-100/70 px-4 py-3 dark:bg-emerald-900/40">
                <div class="text-xs font-medium text-emerald-700 dark:text-emerald-300">Active investments</div>
                <div class="mt-1 text-lg font-bold text-emerald-900 dark:text-emerald-100">{{ $activeCount }}</div>
            </div>
            <div class="rounded-lg bg-emerald-100/70 px-4 py-3 dark:bg-emerald-900/40">
                <div class="text-xs font-medium text-emerald-700 dark:text-emerald-300">Next maturity</div>
                <div class="mt-1 text-lg font-bold text-emerald-900 dark:text-emerald-100">{{ $nextMaturity?->format('d M Y') ?? '—' }}</div>
            </div>
            <div class="rounded-lg bg-emerald-100/70 px-4 py-3 dark:bg-emerald-900/40">
                <div class="text-xs font-medium text-emerald-700 dark:text-emerald-300">Next interest payout</div>
                <div class="mt-1 text-lg font-bold text-emerald-900 dark:text-emerald-100">{{ $nextPayout?->format('d M Y') ?? '—' }}</div>
            </div>
            <a href="{{ $withdrawalsUrl }}" class="rounded-lg bg-emerald-100/70 px-4 py-3 transition hover:bg-emerald-200/70 dark:bg-emerald-900/40 dark:hover:bg-emerald-900/70">
                <div class="text-xs font-medium text-emerald-700 dark:text-emerald-300">Pending withdrawals</div>
                <div class="mt-1 text-lg font-bold text-emerald-900 dark:text-emerald-100">
                    {{ $pendingWithdrawalCount }}
                    @if ($pendingWithdrawalCount > 0)
                        <span class="text-sm font-medium">(${{ number_format($pendingWithdrawalAmount, 2) }})</span>
                    @endif
                </div>
            </a>
        </div>

        {{-- Holdings --}}
        <div class="overflow-hidden rounded-xl border border-emerald-200 bg-white dark:border-emerald-800 dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-emerald-200 px-4 py-3 dark:border-emerald-800">
                <h3 class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">My holdings</h3>
                <a href="{{ $investmentsUrl }}" class="text-xs font-semibold text-emerald-700 hover:underline dark:text-emerald-400">View all &rarr;</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-emerald-50 text-left text-xs uppercase text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
                        <tr>
                            <th class="px-4 py-2">Reference</th>
                            <th class="px-4 py-2">Type</th>
                            <th class="px-4 py-2 text-right">Principal</th>
                            <th class="px-4 py-2 text-right">Current balance</th>
                            <th class="px-4 py-2">Matures</th>
                            <th class="px-4 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-emerald-100 dark:divide-emerald-900">
                        @forelse ($holdings as $holding)
                            <tr>
                                <td class="px-4 py-2 font-semibold text-gray-900 dark:text-gray-100">{{ $holding->reference }}</td>
                                <td class="px-4 py-2">
                                    <x-filament::badge :color="$holding->capital_type?->getColor() ?? 'gray'">{{ $holding->capital_type?->getLabel() ?? '—' }}</x-filament::badge>
                                </td>
                                <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">${{ number_format((float) $holding->principal_amount, 2) }}</td>
                                <td class="px-4 py-2 text-right font-semibold text-emerald-800 dark:text-emerald-300">${{ number_format((float) $holding->current_balance, 2) }}</td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $holding->maturity_date?->format('d M Y') ?? '—' }}</td>
                                <td class="px-4 py-2">
                                    <x-filament::badge :color="$holding->status->getColor()">{{ $holding->status->getLabel() }}</x-filament::badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">You have no active investments yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
