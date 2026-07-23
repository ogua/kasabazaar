<x-filament-panels::page>
    @php
        $investor = $this->getInvestor();
        $valuations = $this->getValuations();
        $asOfDate = $this->getAsOfDate();
        $totalPrincipal = $investor->investments->sum('principal_amount');
        $totalValue = collect($valuations)->sum('compounded_balance');
    @endphp

    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
        Values below reflect posted ledger activity as of {{ $asOfDate->format('F j, Y') }} — matching your Investment
        Agreement. New interest accrues but only appears here once posted.
    </p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Principal Invested</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">${{ number_format($totalPrincipal, 2) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Value as of {{ $asOfDate->format('M j, Y') }}</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">${{ number_format($totalValue, 2) }}</div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Your Investments</h3>
        </div>
        <div class="p-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 dark:text-gray-400">
                        <th class="pb-2">Reference</th>
                        <th class="pb-2 text-right">Principal (USD)</th>
                        <th class="pb-2 text-right">Value as of {{ $asOfDate->format('M j, Y') }} (USD)</th>
                        <th class="pb-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($investor->investments as $investment)
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                            <td class="py-2 font-medium">{{ $investment->reference }}</td>
                            <td class="py-2 text-right">${{ number_format($investment->principal_amount, 2) }}</td>
                            <td class="py-2 text-right">${{ number_format($valuations[$investment->id]['compounded_balance'], 2) }}</td>
                            <td class="py-2">{{ $investment->status->getLabel() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
        Click "Download Statement" above for a full professional statement including payment details, transaction
        history, and withdrawal requests for every investment you hold.
    </p>
</x-filament-panels::page>
