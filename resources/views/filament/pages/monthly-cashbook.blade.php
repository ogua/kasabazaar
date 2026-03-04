<x-filament-panels::page>

    {{-- Month / Year selector --}}
    <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow ring-1 ring-gray-200 dark:ring-gray-700 p-4">
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Month</label>
                <select wire:model.live="selectedMonth"
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500 text-sm">
                    @foreach(\App\Filament\Pages\MonthlyCashbook::getMonthOptions() as $num => $name)
                        <option value="{{ $num }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Year</label>
                <select wire:model.live="selectedYear"
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500 text-sm">
                    @foreach(\App\Filament\Pages\MonthlyCashbook::getYearOptions() as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ml-auto text-right">
                @php
                    $closing = $this->getClosingBalances();
                @endphp
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold mb-1">Closing Balances</p>
                <p class="text-sm font-bold text-gray-800 dark:text-gray-100">
                    Bank: <span class="text-primary-600">₵{{ number_format($closing['bank'], 2) }}</span>
                    &nbsp;|&nbsp;
                    MOMO: <span class="text-info-600">₵{{ number_format($closing['momo'], 2) }}</span>
                </p>
            </div>
        </div>
    </div>

    {{-- Main table --}}
    {{ $this->table }}

    {{-- Monthly Totals --}}
    @php
        $totals = $this->getMonthlyTotals();
    @endphp
    @if(!empty($totals))
    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Receipts summary --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow ring-1 ring-gray-200 dark:ring-gray-700 p-4">
            <h3 class="text-sm font-bold text-green-600 dark:text-green-400 uppercase tracking-wide mb-3">Receipts</h3>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @php
                        $receiptRows = [
                            'op_balance'          => 'Opening Balance',
                            'sales'               => 'Sales',
                            'dir_transfer'        => 'Director Transfer',
                            'shipping_fee'        => 'Shipping Fee',
                            'service_fee'         => 'Service Fee',
                            'momo_interest'       => 'MOMO Interest',
                            'property_management' => 'Property Management',
                            'refund'              => 'Refund',
                            'contra_receipt'      => 'Contra (Receipt)',
                        ];
                    @endphp
                    @foreach($receiptRows as $key => $label)
                        @if(!empty($totals[$key]))
                        <tr>
                            <td class="py-1 text-gray-600 dark:text-gray-300">{{ $label }}</td>
                            <td class="py-1 text-right font-mono font-semibold text-gray-800 dark:text-gray-100">₵{{ number_format($totals[$key], 2) }}</td>
                        </tr>
                        @endif
                    @endforeach
                    <tr class="border-t-2 border-green-500">
                        <td class="py-1 font-bold text-green-700 dark:text-green-400">Total Bank Debit</td>
                        <td class="py-1 text-right font-mono font-bold text-green-700 dark:text-green-400">₵{{ number_format($totals['bank_debit'] ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="py-1 font-bold text-blue-700 dark:text-blue-400">Total MOMO Debit</td>
                        <td class="py-1 text-right font-mono font-bold text-blue-700 dark:text-blue-400">₵{{ number_format($totals['momo_debit'] ?? 0, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Expenditures summary --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow ring-1 ring-gray-200 dark:ring-gray-700 p-4">
            <h3 class="text-sm font-bold text-red-600 dark:text-red-400 uppercase tracking-wide mb-3">Expenditures</h3>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @php
                        $expRows = [
                            'import_duty'       => 'Import Duty Charges',
                            'shipping_expenses' => 'Shipping Expenses',
                            'salaries_wages'    => 'Salaries & Wages',
                            'bank_charges'      => 'Bank Charges',
                            'ssnit'             => 'SSNIT',
                            'paye'              => 'PAYE',
                            'withholding_tax'   => 'Withholding Tax',
                            'momo_charges'      => 'MOMO Charges',
                            'contra_payment'    => 'Contra (Payment)',
                            'transportation'    => 'Transportation',
                            'materials'         => 'Materials',
                            'donation'          => 'Donation',
                        ];
                    @endphp
                    @foreach($expRows as $key => $label)
                        @if(!empty($totals[$key]))
                        <tr>
                            <td class="py-1 text-gray-600 dark:text-gray-300">{{ $label }}</td>
                            <td class="py-1 text-right font-mono font-semibold text-gray-800 dark:text-gray-100">₵{{ number_format($totals[$key], 2) }}</td>
                        </tr>
                        @endif
                    @endforeach
                    <tr class="border-t-2 border-red-500">
                        <td class="py-1 font-bold text-red-700 dark:text-red-400">Total Bank Credit</td>
                        <td class="py-1 text-right font-mono font-bold text-red-700 dark:text-red-400">₵{{ number_format($totals['bank_credit'] ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="py-1 font-bold text-orange-700 dark:text-orange-400">Total MOMO Credit</td>
                        <td class="py-1 text-right font-mono font-bold text-orange-700 dark:text-orange-400">₵{{ number_format($totals['momo_credit'] ?? 0, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
