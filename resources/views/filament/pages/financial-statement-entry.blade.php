<x-filament-panels::page>
    @php
        $period = $this->period();
        $check = $this->trialBalance();
        $accounts = $this->accounts()->groupBy(fn ($account) => $account->type->getLabel());
        // These totals are the keyed figures, so they carry the entry currency —
        // not the USD the statements present in.
        $symbol = $this->entryCurrency === 'GHS' ? 'GHS ' : '$';
        $money = fn ($amount) => $symbol.number_format((float) $amount, 2);
        $isLocked = $period->status === \App\Enums\FiscalPeriodStatus::locked;
    @endphp

    <form wire:submit.prevent>
        {{ $this->form }}
    </form>

    <x-filament::section>
        <x-slot name="heading">Trial balance for {{ $this->selectedYear }}</x-slot>
        <x-slot name="description">
            Enter each account's closing position for the year, taken from the accounts prepared by
            the company's accountant. These figures become the source for the Profit &amp; Loss and
            Balance Sheet pages for this year — the system holds no transactions for it.
            <strong>Key the figures exactly as they appear in those accounts</strong>, in the currency
            selected above; the statements translate them into USD for presentation.
        </x-slot>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-4">
            <div class="rounded-lg bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Debits</div>
                <div class="mt-1 text-xl font-bold tabular-nums">{{ $money($check['debits']) }}</div>
            </div>
            <div class="rounded-lg bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Credits</div>
                <div class="mt-1 text-xl font-bold tabular-nums">{{ $money($check['credits']) }}</div>
            </div>
            <div @class([
                'rounded-lg p-4 ring-1',
                'bg-success-50 ring-success-600/20 dark:bg-success-950/50 dark:ring-success-400/20' => $check['balances'],
                'bg-warning-50 ring-warning-600/20 dark:bg-warning-950/50 dark:ring-warning-400/20' => ! $check['balances'],
            ])>
                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Difference</div>
                <div @class([
                    'mt-1 text-xl font-bold tabular-nums',
                    'text-success-700 dark:text-success-300' => $check['balances'],
                    'text-warning-700 dark:text-warning-300' => ! $check['balances'],
                ])>
                    {{ $money($check['difference']) }}
                </div>
                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ $check['balances'] ? 'The trial balance articulates.' : 'Debits and credits must agree before the year can be locked.' }}
                </div>
            </div>
        </div>

        @if ($this->entryCurrency === 'GHS')
            <div class="mb-4 rounded-lg border border-info-300 bg-info-50 p-4 dark:border-info-700 dark:bg-info-950/50">
                <p class="text-sm text-info-800 dark:text-info-200">
                    Entering in <strong>Ghana Cedis</strong>.
                    @if (filled($this->closingRate))
                        These figures will be translated at GHS {{ number_format((float) $this->closingRate, 4) }}
                        to USD 1.00 on the statements — so a keyed GHS 1,000.00 shows as
                        ${{ number_format(1000 / max((float) $this->closingRate, 0.0001), 2) }}.
                    @else
                        <strong>Set the year-end rate above</strong> before locking the year, or the figures
                        cannot be translated into the presentation currency.
                    @endif
                </p>
            </div>
        @endif

        @if ($isLocked)
            <div class="mb-4 rounded-lg border border-gray-300 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    <strong>{{ $this->selectedYear }} is locked.</strong>
                    Locked {{ $period->locked_at?->format('F j, Y') }}
                    @if ($period->lockedBy)
                        by {{ $period->lockedBy->name }}
                    @endif.
                    Its balances are read-only.
                </p>
            </div>
        @endif

        @foreach ($accounts as $typeLabel => $group)
            <div class="mb-6">
                <h3 class="text-xs font-bold uppercase tracking-wide text-primary-600 dark:text-primary-400 mb-2">
                    {{ $typeLabel }}
                    <span class="font-normal normal-case text-gray-400">
                        — {{ $group->first()->type->isDebitNormal() ? 'debit balances' : 'credit balances' }}
                    </span>
                </h3>

                <div class="overflow-x-auto rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10">
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                            @foreach ($group as $account)
                                <tr wire:key="account-{{ $account->id }}">
                                    <td class="px-4 py-2">
                                        <div class="text-gray-950 dark:text-white">{{ $account->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $account->code }} · {{ $account->statement_line }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 w-48">
                                        <input
                                            type="number"
                                            step="0.01"
                                            inputmode="decimal"
                                            @disabled($isLocked)
                                            wire:model.blur="balances.{{ $account->id }}"
                                            placeholder="0.00"
                                            class="w-full rounded-lg border-gray-300 text-right tabular-nums shadow-sm disabled:opacity-50 dark:border-white/20 dark:bg-white/5 dark:text-white"
                                        >
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </x-filament::section>
</x-filament-panels::page>
