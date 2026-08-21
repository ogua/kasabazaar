<x-filament-panels::page>
    @php
        $statement = $this->statement();
        $totals = $statement['totals'];
        $money = fn ($amount) => '$'.number_format((float) $amount, 2);
        $outstanding = (float) $totals['outstanding'];
    @endphp

    <form wire:submit.prevent>
        {{ $this->form }}
    </form>

    <x-filament::section>
        <x-slot name="heading">As at {{ \Carbon\Carbon::parse($statement['as_of'])->format('F j, Y') }}</x-slot>
        <x-slot name="description">
            The unpaid portion of each shipment (invoiced less received), aged from the date the
            shipment was raised. Fully settled shipments are excluded.
        </x-slot>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 mb-6">
            @foreach ($statement['aging'] as $bucket)
                <div class="rounded-lg bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ $bucket['bucket'] }} days
                    </div>
                    <div @class([
                        'mt-1 text-xl font-bold tabular-nums',
                        'text-danger-600 dark:text-danger-400' => $bucket['bucket'] === '90+' && $bucket['amount'] > 0,
                        'text-gray-950 dark:text-white' => ! ($bucket['bucket'] === '90+' && $bucket['amount'] > 0),
                    ])>
                        {{ $money($bucket['amount']) }}
                    </div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ $bucket['count'] }} shipment{{ $bucket['count'] === 1 ? '' : 's' }}
                        @if ($outstanding > 0)
                            · {{ number_format($bucket['amount'] / $outstanding * 100, 1) }}%
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="overflow-x-auto rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <th class="px-4 py-2 text-left">Client</th>
                        <th class="px-4 py-2 text-right">Shipments</th>
                        <th class="px-4 py-2 text-right">0–30</th>
                        <th class="px-4 py-2 text-right">31–60</th>
                        <th class="px-4 py-2 text-right">61–90</th>
                        <th class="px-4 py-2 text-right">90+</th>
                        <th class="px-4 py-2 text-right">Total</th>
                        <th class="px-4 py-2 text-right">Oldest</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @forelse ($statement['clients'] as $client)
                        <tr>
                            <td class="px-4 py-2 text-gray-950 dark:text-white">{{ $client['client'] }}</td>
                            <td class="px-4 py-2 text-right tabular-nums">{{ $client['shipment_count'] }}</td>
                            <td class="px-4 py-2 text-right tabular-nums">{{ $money($client['buckets']['0-30']) }}</td>
                            <td class="px-4 py-2 text-right tabular-nums">{{ $money($client['buckets']['31-60']) }}</td>
                            <td class="px-4 py-2 text-right tabular-nums">{{ $money($client['buckets']['61-90']) }}</td>
                            <td @class([
                                'px-4 py-2 text-right tabular-nums',
                                'text-danger-600 dark:text-danger-400 font-semibold' => $client['buckets']['90+'] > 0,
                            ])>{{ $money($client['buckets']['90+']) }}</td>
                            <td class="px-4 py-2 text-right font-bold tabular-nums text-gray-950 dark:text-white">
                                {{ $money($client['outstanding']) }}
                            </td>
                            <td class="px-4 py-2 text-right tabular-nums text-gray-500 dark:text-gray-400">
                                {{ $client['oldest_days'] }}d
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-4 text-gray-400 dark:text-gray-500">
                                Nothing outstanding at this date.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="border-t-2 border-gray-300 bg-gray-100 dark:border-white/20 dark:bg-white/10">
                    <tr>
                        <td class="px-4 py-2 font-bold text-gray-950 dark:text-white">
                            Total ({{ $totals['client_count'] }} clients)
                        </td>
                        <td class="px-4 py-2 text-right font-bold tabular-nums">{{ $totals['shipment_count'] }}</td>
                        <td colspan="4"></td>
                        <td class="px-4 py-2 text-right font-bold tabular-nums text-gray-950 dark:text-white">
                            {{ $money($outstanding) }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
