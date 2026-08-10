<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <x-filament::section>
            <div class="text-sm text-gray-500 dark:text-gray-400">Available</div>
            <div class="text-2xl font-semibold">GHS {{ number_format($summary['balance_ghs'] ?? 0, 2) }}</div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm text-gray-500 dark:text-gray-400">Pending</div>
            <div class="text-2xl font-semibold">GHS {{ number_format($summary['pending_balance_ghs'] ?? 0, 2) }}</div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm text-gray-500 dark:text-gray-400">Lifetime</div>
            <div class="text-2xl font-semibold">GHS {{ number_format($summary['lifetime_earnings_ghs'] ?? 0, 2) }}</div>
        </x-filament::section>
    </div>

    <x-filament::section heading="Transaction History" class="mt-4">
        <table class="fi-ta-table w-full text-start">
            <thead>
                <tr>
                    <th class="p-2 text-start text-xs font-medium text-gray-500">Date</th>
                    <th class="p-2 text-start text-xs font-medium text-gray-500">Type</th>
                    <th class="p-2 text-start text-xs font-medium text-gray-500">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $tx)
                    <tr class="border-t border-gray-100 dark:border-white/5">
                        <td class="p-2">{{ \Illuminate\Support\Carbon::parse($tx['created_at'])->format('M d, Y') }}</td>
                        <td class="p-2">{{ ucfirst(str_replace('_', ' ', $tx['type'])) }}</td>
                        <td class="p-2">GHS {{ number_format($tx['amount_ghs'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="p-2 text-sm text-gray-500 dark:text-gray-400">No transactions yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-filament::section>
</x-filament-panels::page>
