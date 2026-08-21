@php
    /**
     * One captioned block of a statement: the statement lines, their underlying
     * accounts, and a subtotal. Shared by the P&L and Balance Sheet pages so both
     * read identically on screen and against the printed PDF.
     */
    $money = fn ($amount) => ($amount < 0 ? '-' : '').'$'.number_format(abs((float) $amount), 2);
@endphp

<div class="mb-6">
    <h3 class="text-xs font-bold uppercase tracking-wide text-primary-600 dark:text-primary-400 mb-2">
        {{ $heading }}
    </h3>

    <div class="overflow-x-auto rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10">
        <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                @forelse ($groups as $group)
                    <tr class="bg-gray-50 dark:bg-white/5">
                        <td class="px-4 py-2 font-semibold text-gray-950 dark:text-white">{{ $group['statement_line'] }}</td>
                        <td class="px-4 py-2 text-right font-semibold tabular-nums text-gray-950 dark:text-white">
                            {{ $money($group['amount']) }}
                        </td>
                    </tr>
                    @foreach ($group['accounts'] as $account)
                        <tr>
                            <td class="px-4 py-1.5 pl-8 text-gray-500 dark:text-gray-400">{{ $account['name'] }}</td>
                            <td class="px-4 py-1.5 text-right tabular-nums text-gray-500 dark:text-gray-400">
                                {{ $money($account['amount']) }}
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="2" class="px-4 py-3 text-gray-400 dark:text-gray-500">
                            No amounts recorded for this year.
                        </td>
                    </tr>
                @endforelse

                @isset($total)
                    <tr class="border-t-2 border-gray-300 bg-gray-100 dark:border-white/20 dark:bg-white/10">
                        <td class="px-4 py-2 font-bold text-gray-950 dark:text-white">{{ $totalLabel ?? 'Total' }}</td>
                        <td class="px-4 py-2 text-right font-bold tabular-nums text-gray-950 dark:text-white">
                            {{ $money($total) }}
                        </td>
                    </tr>
                @endisset
            </tbody>
        </table>
    </div>
</div>
