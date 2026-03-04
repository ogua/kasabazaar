<x-filament-panels::page>

    {{-- ── Month / Year selector ───────────────────────────────── --}}
    <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow ring-1 ring-gray-200 dark:ring-gray-700 p-4">
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Month</label>
                <select wire:model.live="selectedMonth"
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500 text-sm">
                    @foreach(\App\Filament\Pages\CashbookReport::getMonthOptions() as $num => $name)
                        <option value="{{ $num }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Year</label>
                <select wire:model.live="selectedYear"
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500 text-sm">
                    @foreach(\App\Filament\Pages\CashbookReport::getYearOptions() as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ml-auto text-sm text-gray-500 dark:text-gray-400">
                Use the <span class="font-semibold text-danger-600">Export PDF</span> or
                <span class="font-semibold text-success-600">Export Excel</span> buttons above to download.
            </div>
        </div>
    </div>

    @php
        $data    = $this->getReportData();
        $summary = $data['summary'];
        $totals  = $data['totals'];
        $entries = $data['entries'];
        $wht     = $data['whtEntries'];
    @endphp

    {{-- ── Summary Cards ───────────────────────────────────────── --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        {{-- Opening Bank --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow ring-1 ring-gray-200 dark:ring-gray-700 p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Opening Bank</div>
            <div class="text-xl font-bold text-info-600 mt-1">₵{{ number_format($summary['opening_bank'], 2) }}</div>
        </div>
        {{-- Opening MOMO --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow ring-1 ring-gray-200 dark:ring-gray-700 p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Opening MOMO</div>
            <div class="text-xl font-bold text-info-600 mt-1">₵{{ number_format($summary['opening_momo'], 2) }}</div>
        </div>
        {{-- Closing Bank --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow ring-1 ring-gray-200 dark:ring-gray-700 p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Closing Bank</div>
            <div class="text-xl font-bold {{ $summary['closing_bank'] >= $summary['opening_bank'] ? 'text-success-600' : 'text-danger-600' }} mt-1">
                ₵{{ number_format($summary['closing_bank'], 2) }}
            </div>
        </div>
        {{-- Closing MOMO --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow ring-1 ring-gray-200 dark:ring-gray-700 p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Closing MOMO</div>
            <div class="text-xl font-bold {{ $summary['closing_momo'] >= $summary['opening_momo'] ? 'text-success-600' : 'text-danger-600' }} mt-1">
                ₵{{ number_format($summary['closing_momo'], 2) }}
            </div>
        </div>
        {{-- Total Receipts --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow ring-1 ring-gray-200 dark:ring-gray-700 p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Total Receipts</div>
            <div class="text-xl font-bold text-success-600 mt-1">₵{{ number_format($summary['total_receipts'], 2) }}</div>
            <div class="text-xs text-gray-400 mt-1">Bank: ₵{{ number_format($totals['bank_debit'] ?? 0, 2) }} · MOMO: ₵{{ number_format($totals['momo_debit'] ?? 0, 2) }}</div>
        </div>
        {{-- Total Expenditures --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow ring-1 ring-gray-200 dark:ring-gray-700 p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Total Expenditures</div>
            <div class="text-xl font-bold text-danger-600 mt-1">₵{{ number_format($summary['total_expenditures'], 2) }}</div>
            <div class="text-xs text-gray-400 mt-1">Bank: ₵{{ number_format($totals['bank_credit'] ?? 0, 2) }} · MOMO: ₵{{ number_format($totals['momo_credit'] ?? 0, 2) }}</div>
        </div>
        {{-- Net Position --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow ring-1 ring-gray-200 dark:ring-gray-700 p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Net Position</div>
            <div class="text-xl font-bold {{ $summary['net_position'] >= 0 ? 'text-success-600' : 'text-danger-600' }} mt-1">
                ₵{{ number_format($summary['net_position'], 2) }}
            </div>
            <div class="text-xs text-gray-400 mt-1">Receipts − Expenditures</div>
        </div>
        {{-- Entries --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow ring-1 ring-gray-200 dark:ring-gray-700 p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Entries</div>
            <div class="text-xl font-bold text-gray-700 dark:text-gray-200 mt-1">{{ $summary['entry_count'] }}</div>
            <div class="text-xs text-gray-400 mt-1">Transactions this month</div>
        </div>
    </div>

    {{-- ── Receipts + Expenditures Side by Side ───────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">

        {{-- Receipts Breakdown --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">
            <div class="bg-success-600 text-white px-4 py-2 text-sm font-bold uppercase tracking-wide">
                Receipts by Cost Centre
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700">
                        <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Category</th>
                        <th class="text-right px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Amount (GH₵)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($summary['receipts_breakdown'] as $label => $amount)
                    @if($amount > 0)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $label }}</td>
                        <td class="px-4 py-2 text-right font-mono font-semibold text-success-600">{{ number_format($amount, 2) }}</td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-success-50 dark:bg-success-900/30">
                        <td class="px-4 py-2 font-bold text-success-700 dark:text-success-400">Total Bank Debit</td>
                        <td class="px-4 py-2 text-right font-mono font-bold text-success-700 dark:text-success-400">{{ number_format($totals['bank_debit'] ?? 0, 2) }}</td>
                    </tr>
                    <tr class="bg-info-50 dark:bg-info-900/30">
                        <td class="px-4 py-2 font-bold text-info-700 dark:text-info-400">Total MOMO Debit</td>
                        <td class="px-4 py-2 text-right font-mono font-bold text-info-700 dark:text-info-400">{{ number_format($totals['momo_debit'] ?? 0, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Expenditures Breakdown --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">
            <div class="bg-danger-600 text-white px-4 py-2 text-sm font-bold uppercase tracking-wide">
                Expenditures by Cost Centre
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700">
                        <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Category</th>
                        <th class="text-right px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Amount (GH₵)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($summary['expenditures_breakdown'] as $label => $amount)
                    @if($amount > 0)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $label }}</td>
                        <td class="px-4 py-2 text-right font-mono font-semibold text-danger-600">{{ number_format($amount, 2) }}</td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-danger-50 dark:bg-danger-900/30">
                        <td class="px-4 py-2 font-bold text-danger-700 dark:text-danger-400">Total Bank Credit</td>
                        <td class="px-4 py-2 text-right font-mono font-bold text-danger-700 dark:text-danger-400">{{ number_format($totals['bank_credit'] ?? 0, 2) }}</td>
                    </tr>
                    <tr class="bg-warning-50 dark:bg-warning-900/30">
                        <td class="px-4 py-2 font-bold text-warning-700 dark:text-warning-400">Total MOMO Credit</td>
                        <td class="px-4 py-2 text-right font-mono font-bold text-warning-700 dark:text-warning-400">{{ number_format($totals['momo_credit'] ?? 0, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- ── Bank Reconciliation ───────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow ring-1 ring-primary-200 dark:ring-primary-700 overflow-hidden mb-6">
        <div class="bg-primary-700 text-white px-4 py-2 text-sm font-bold uppercase tracking-wide">
            Bank Reconciliation Statement — {{ $data['monthName'] }}
        </div>
        <div class="p-4 space-y-2 text-sm">
            <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700">
                <span class="text-gray-600 dark:text-gray-400">Balance as per Cash Book (Bank)</span>
                <span class="font-mono font-bold text-gray-800 dark:text-gray-100">₵{{ number_format($summary['closing_bank'], 2) }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700">
                <span class="text-gray-600 dark:text-gray-400">Add: Unpresented Cheques (issued, not cleared)</span>
                <span class="font-mono text-gray-500">₵0.00</span>
            </div>
            <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700">
                <span class="text-gray-600 dark:text-gray-400">Less: Uncredited Cheques (received, not banked)</span>
                <span class="font-mono text-gray-500">₵0.00</span>
            </div>
            <div class="flex justify-between py-2 border-t-2 border-primary-500">
                <span class="font-bold text-gray-800 dark:text-gray-100">Balance as per Bank Statement</span>
                <span class="font-mono font-bold text-primary-700 dark:text-primary-400 text-base">₵{{ number_format($summary['closing_bank'], 2) }}</span>
            </div>
            <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700 mt-1">
                <span class="text-gray-600 dark:text-gray-400">MOMO Closing Balance</span>
                <span class="font-mono font-bold text-info-600">₵{{ number_format($summary['closing_momo'], 2) }}</span>
            </div>
        </div>
    </div>

    {{-- ── Transactions Table ─────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden mb-6">
        <div class="bg-gray-700 dark:bg-gray-600 text-white px-4 py-2 text-sm font-bold uppercase tracking-wide flex items-center justify-between">
            <span>All Transactions — {{ $data['monthName'] }}</span>
            <span class="text-xs font-normal opacity-75">{{ count($entries) }} entries</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700">
                        <th class="px-3 py-2 text-left text-gray-500 uppercase whitespace-nowrap">Date</th>
                        <th class="px-3 py-2 text-left text-gray-500 uppercase whitespace-nowrap">PV</th>
                        <th class="px-3 py-2 text-left text-gray-500 uppercase">Details</th>
                        <th class="px-3 py-2 text-center text-gray-500 uppercase">CHQ</th>
                        <th class="px-3 py-2 text-right text-success-600 uppercase whitespace-nowrap">Bank Dr</th>
                        <th class="px-3 py-2 text-right text-success-500 uppercase whitespace-nowrap">MOMO Dr</th>
                        <th class="px-3 py-2 text-right text-danger-600 uppercase whitespace-nowrap">Bank Cr</th>
                        <th class="px-3 py-2 text-right text-danger-500 uppercase whitespace-nowrap">MOMO Cr</th>
                        <th class="px-3 py-2 text-right text-info-700 uppercase font-bold whitespace-nowrap">Bank Bal</th>
                        <th class="px-3 py-2 text-right text-info-600 uppercase font-bold whitespace-nowrap">MOMO Bal</th>
                        <th class="px-3 py-2 text-left text-gray-500 uppercase whitespace-nowrap">Cost Center</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($entries as $entry)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ $loop->even ? 'bg-gray-50/50 dark:bg-gray-700/20' : '' }}">
                        <td class="px-3 py-1.5 whitespace-nowrap text-gray-600 dark:text-gray-400">
                            {{ \Carbon\Carbon::parse($entry['date'])->format('d M') }}
                        </td>
                        <td class="px-3 py-1.5 text-gray-500">{{ $entry['pv_no'] ?? '—' }}</td>
                        <td class="px-3 py-1.5 text-gray-800 dark:text-gray-200 max-w-xs truncate">{{ $entry['details'] }}</td>
                        <td class="px-3 py-1.5 text-center text-gray-500">{{ $entry['chq_ref'] ?? '—' }}</td>
                        <td class="px-3 py-1.5 text-right font-mono {{ $entry['bank_debit'] > 0 ? 'text-success-600 font-semibold' : 'text-gray-300' }}">
                            {{ $entry['bank_debit'] > 0 ? number_format($entry['bank_debit'], 2) : '—' }}
                        </td>
                        <td class="px-3 py-1.5 text-right font-mono {{ $entry['momo_debit'] > 0 ? 'text-success-500 font-semibold' : 'text-gray-300' }}">
                            {{ $entry['momo_debit'] > 0 ? number_format($entry['momo_debit'], 2) : '—' }}
                        </td>
                        <td class="px-3 py-1.5 text-right font-mono {{ $entry['bank_credit'] > 0 ? 'text-danger-600 font-semibold' : 'text-gray-300' }}">
                            {{ $entry['bank_credit'] > 0 ? number_format($entry['bank_credit'], 2) : '—' }}
                        </td>
                        <td class="px-3 py-1.5 text-right font-mono {{ $entry['momo_credit'] > 0 ? 'text-danger-500 font-semibold' : 'text-gray-300' }}">
                            {{ $entry['momo_credit'] > 0 ? number_format($entry['momo_credit'], 2) : '—' }}
                        </td>
                        <td class="px-3 py-1.5 text-right font-mono font-bold text-info-700 dark:text-info-400">
                            {{ number_format($entry['bank_balance'], 2) }}
                        </td>
                        <td class="px-3 py-1.5 text-right font-mono font-bold text-info-600 dark:text-info-300">
                            {{ number_format($entry['momo_balance'], 2) }}
                        </td>
                        <td class="px-3 py-1.5">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                {{ $entry['is_receipt'] ? 'bg-success-100 text-success-800 dark:bg-success-900/40 dark:text-success-300' : 'bg-danger-100 text-danger-800 dark:bg-danger-900/40 dark:text-danger-300' }}">
                                {{ $entry['cost_center_label'] }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">
                            No entries for {{ $data['monthName'] }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($entries) > 0)
                <tfoot>
                    <tr class="bg-gray-800 dark:bg-gray-900 text-white">
                        <td colspan="4" class="px-3 py-2 font-bold text-xs uppercase">Monthly Totals</td>
                        <td class="px-3 py-2 text-right font-mono font-bold text-success-300">{{ number_format($totals['bank_debit'] ?? 0, 2) }}</td>
                        <td class="px-3 py-2 text-right font-mono font-bold text-success-200">{{ number_format($totals['momo_debit'] ?? 0, 2) }}</td>
                        <td class="px-3 py-2 text-right font-mono font-bold text-danger-300">{{ number_format($totals['bank_credit'] ?? 0, 2) }}</td>
                        <td class="px-3 py-2 text-right font-mono font-bold text-danger-200">{{ number_format($totals['momo_credit'] ?? 0, 2) }}</td>
                        <td class="px-3 py-2 text-right font-mono font-bold text-info-300">{{ number_format($summary['closing_bank'], 2) }}</td>
                        <td class="px-3 py-2 text-right font-mono font-bold text-info-200">{{ number_format($summary['closing_momo'], 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- ── WHT Breakdown ─────────────────────────────────────────── --}}
    @if(!empty($wht))
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">
        <div class="bg-purple-700 text-white px-4 py-2 text-sm font-bold uppercase tracking-wide flex items-center justify-between">
            <span>Withholding Tax Breakdown — {{ $data['monthName'] }}</span>
            <span class="text-xs font-normal opacity-75">Rate: 7.5%</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700">
                        <th class="px-4 py-2 text-left text-gray-500 uppercase text-xs">Staff Name</th>
                        <th class="px-4 py-2 text-right text-gray-500 uppercase text-xs">Gross Amount</th>
                        <th class="px-4 py-2 text-right text-gray-500 uppercase text-xs">Rate</th>
                        <th class="px-4 py-2 text-right text-gray-500 uppercase text-xs">WHT Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($wht as $w)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $w['staff_name'] }}</td>
                        <td class="px-4 py-2 text-right font-mono text-gray-600 dark:text-gray-400">₵{{ number_format($w['gross_amount'], 2) }}</td>
                        <td class="px-4 py-2 text-right font-mono text-gray-500">{{ number_format($w['rate'] * 100, 1) }}%</td>
                        <td class="px-4 py-2 text-right font-mono font-bold text-danger-600">₵{{ number_format($w['wht_amount'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-purple-50 dark:bg-purple-900/30">
                        <td colspan="3" class="px-4 py-2 font-bold text-purple-800 dark:text-purple-300">Total WHT Payable</td>
                        <td class="px-4 py-2 text-right font-mono font-bold text-purple-800 dark:text-purple-300">
                            ₵{{ number_format(collect($wht)->sum('wht_amount'), 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
