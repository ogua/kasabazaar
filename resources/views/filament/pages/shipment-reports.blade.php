<x-filament-panels::page>
    <form wire:submit="generateReport">
        {{ $this->form }}

        <div class="mt-4 flex items-center gap-3">
            <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="generateReport">
                <span wire:loading.remove wire:target="generateReport">Generate Report</span>
                <span wire:loading wire:target="generateReport">
                    <x-filament::loading-indicator class="h-4 w-4 inline-block mr-2" />
                    Generating...
                </span>
            </x-filament::button>

            @if($this->reportData && $this->reportData->count() > 0)
                <div class="flex gap-2">
                    <x-filament::button
                        color="success"
                        icon="heroicon-o-document-arrow-down"
                        wire:click="exportPdf"
                        wire:loading.attr="disabled"
                        wire:target="exportPdf"
                    >
                        <span wire:loading.remove wire:target="exportPdf">Export PDF</span>
                        <span wire:loading wire:target="exportPdf">Exporting...</span>
                    </x-filament::button>

                    <x-filament::button
                        color="success"
                        icon="heroicon-o-table-cells"
                        wire:click="exportExcel"
                        wire:loading.attr="disabled"
                        wire:target="exportExcel"
                    >
                        <span wire:loading.remove wire:target="exportExcel">Export Excel</span>
                        <span wire:loading wire:target="exportExcel">Exporting...</span>
                    </x-filament::button>

                    <x-filament::button
                        color="success"
                        icon="heroicon-o-document-text"
                        wire:click="exportWord"
                        wire:loading.attr="disabled"
                        wire:target="exportWord"
                    >
                        <span wire:loading.remove wire:target="exportWord">Export Word</span>
                        <span wire:loading wire:target="exportWord">Exporting...</span>
                    </x-filament::button>
                </div>
            @endif
        </div>
    </form>

    @if($this->reportData && $this->reportData->count() > 0)
        <div class="mt-6">
            <x-filament::section>
                <x-slot name="heading">
                    Report Results
                </x-slot>

                @if($this->report_type === 'by_container' || $this->report_type === 'client_shipments' || $this->report_type === 'by_date_range')
                    {{-- PDF-Style Container Report --}}
                    <div class="space-y-6">
                        @foreach($this->reportData as $shipment)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-white dark:bg-gray-800">
                                {{-- Client Header --}}
                                <div class="mb-3 pb-3 border-b border-gray-200 dark:border-gray-700">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="font-semibold text-base">
                                                CLIENT: {{ strtoupper($shipment->client?->company_name ?? $shipment->client?->name ?? 'N/A') }}
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                Phone: {{ $shipment->client?->phone ?? 'N/A' }}
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                Reference: <span class="font-medium">{{ $shipment->shipping_reference }}</span>
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                Date: <span class="font-medium">{{ $shipment->created_at?->format('M d, Y') ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <x-filament::badge :color="$shipment->status?->getColor() ?? 'gray'">
                                                {{ $shipment->status?->getLabel() ?? 'Unknown' }}
                                            </x-filament::badge>
                                            <div class="text-sm mt-2 font-medium">
                                                Total: ${{ number_format($shipment->total ?? 0, 2) }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                Paid: ${{ number_format($shipment->paid ?? 0, 2) }}
                                            </div>
                                            @if(($shipment->total - $shipment->paid) > 0)
                                                <div class="text-xs text-red-600 dark:text-red-400">
                                                    Balance: ${{ number_format($shipment->total - $shipment->paid, 2) }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Receivers and Items --}}
                                <div class="space-y-4">
                                    @forelse($shipment->receivers as $receiver)
                                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded p-3">
                                            <div class="mb-2">
                                                <div class="font-medium text-sm">
                                                    RECEIVER: {{ strtoupper($receiver->receiver_name ?: 'SELF') }}
                                                </div>
                                                @if($receiver->receiver_phone)
                                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                                        Contact: {{ $receiver->receiver_phone }}
                                                    </div>
                                                @endif
                                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                                    Location: {{ strtoupper($receiver->city ?? $receiver->address ?? 'N/A') }}
                                                </div>
                                            </div>

                                            {{-- Items List --}}
                                            @if($receiver->items && $receiver->items->count() > 0)
                                                <div class="mt-2">
                                                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">ITEMS</div>
                                                    <ul class="list-disc list-inside text-sm space-y-0.5 text-gray-700 dark:text-gray-300">
                                                        @foreach($receiver->items as $item)
                                                            <li>{{ strtoupper($item->product?->name ?? $item->description ?? 'N/A') }}
                                                                @if($item->quantity > 1)
                                                                    <span class="text-gray-500">({{ $item->quantity }})</span>
                                                                @endif
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @else
                                                <div class="text-sm text-gray-500 italic">No items listed</div>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="text-sm text-gray-500 italic">No receivers listed</div>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- Table Format for other reports --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b dark:border-gray-700">
                                    @if($this->report_type === 'by_year')
                                        <th class="px-4 py-2 text-left">Reference</th>
                                        <th class="px-4 py-2 text-left">Client</th>
                                        <th class="px-4 py-2 text-left">Status</th>
                                        <th class="px-4 py-2 text-right">Total (USD)</th>
                                        <th class="px-4 py-2 text-left">Date</th>
                                    @elseif($this->report_type === 'profit_loss')
                                        <th class="px-4 py-2 text-left">Container / Client</th>
                                        <th class="px-4 py-2 text-right">Shipments</th>
                                        <th class="px-4 py-2 text-right">Revenue / Total (USD)</th>
                                        <th class="px-4 py-2 text-right">Expenses / Paid (USD)</th>
                                        <th class="px-4 py-2 text-right">Profit / Balance (USD)</th>
                                        <th class="px-4 py-2 text-right">Margin / Status</th>
                                    @elseif($this->report_type === 'debtors')
                                        <th class="px-4 py-2 text-left">Container / Client</th>
                                        <th class="px-4 py-2 text-right">Shipments</th>
                                        <th class="px-4 py-2 text-right">Total Owed (USD)</th>
                                        <th class="px-4 py-2 text-right">Paid (USD)</th>
                                        <th class="px-4 py-2 text-right">Balance (USD)</th>
                                        <th class="px-4 py-2 text-right">Days Outstanding / Status</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->reportData as $row)
                                    @if($this->report_type === 'by_year')
                                        <tr class="border-b dark:border-gray-700">
                                            <td class="px-4 py-2">{{ $row->shipping_reference ?? 'N/A' }}</td>
                                            <td class="px-4 py-2">{{ $row->client?->company_name ?? $row->client?->name ?? 'N/A' }}</td>
                                            <td class="px-4 py-2">
                                                <x-filament::badge :color="$row->status?->getColor() ?? 'gray'">
                                                    {{ $row->status?->getLabel() ?? 'Unknown' }}
                                                </x-filament::badge>
                                            </td>
                                            <td class="px-4 py-2 text-right">${{ number_format($row->total ?? 0, 2) }}</td>
                                            <td class="px-4 py-2">{{ $row->created_at?->format('M d, Y') ?? 'N/A' }}</td>
                                        </tr>
                                    @elseif($this->report_type === 'profit_loss')
                                        @php
                                            $rev = $row['revenue'] ?? 0;
                                            $exp = $row['expenses'] ?? 0;
                                            $pft = $row['profit'] ?? 0;
                                            $mgn = $rev > 0 ? round(($pft / $rev) * 100, 2) : 0;
                                        @endphp
                                        {{-- Container summary row --}}
                                        <tr class="border-b dark:border-gray-700 bg-gray-100 dark:bg-gray-900/50 font-semibold">
                                            <td class="px-4 py-2">{{ $row['container'] ?? 'N/A' }}</td>
                                            <td class="px-4 py-2 text-right">{{ $row['shipment_count'] ?? 0 }}</td>
                                            <td class="px-4 py-2 text-right">${{ number_format($rev, 2) }}</td>
                                            <td class="px-4 py-2 text-right">${{ number_format($exp, 2) }}</td>
                                            <td class="px-4 py-2 text-right {{ $pft >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                ${{ number_format($pft, 2) }}
                                            </td>
                                            <td class="px-4 py-2 text-right">{{ $mgn }}%</td>
                                        </tr>
                                        {{-- Client breakdown rows --}}
                                        @foreach($row['clients'] ?? [] as $client)
                                            @php
                                                $badgeColor = match($client['payment_status']) {
                                                    'paid' => 'success',
                                                    'partial' => 'warning',
                                                    default => 'danger',
                                                };
                                                $badgeLabel = match($client['payment_status']) {
                                                    'paid' => 'Paid',
                                                    'partial' => 'Partial',
                                                    default => 'Unpaid',
                                                };
                                            @endphp
                                            <tr class="border-b border-gray-100 dark:border-gray-800 text-sm">
                                                <td class="px-4 py-1.5 pl-8" colspan="2">
                                                    <span class="font-medium">{{ $client['name'] }}</span>
                                                    @if($client['phone'])
                                                        <span class="text-xs text-gray-500 ml-2">{{ $client['phone'] }}</span>
                                                    @endif
                                                    <span class="text-xs text-gray-400 ml-1">({{ $client['shipment_count'] }} shipment{{ $client['shipment_count'] > 1 ? 's' : '' }})</span>
                                                </td>
                                                <td class="px-4 py-1.5 text-right">${{ number_format($client['total'], 2) }}</td>
                                                <td class="px-4 py-1.5 text-right text-green-600 dark:text-green-400">${{ number_format($client['paid'], 2) }}</td>
                                                <td class="px-4 py-1.5 text-right {{ $client['balance'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                                    ${{ number_format($client['balance'], 2) }}
                                                </td>
                                                <td class="px-4 py-1.5 text-right">
                                                    <x-filament::badge :color="$badgeColor">{{ $badgeLabel }}</x-filament::badge>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @elseif($this->report_type === 'debtors')
                                        {{-- Container summary row --}}
                                        <tr class="border-b dark:border-gray-700 bg-gray-100 dark:bg-gray-900/50 font-semibold">
                                            <td class="px-4 py-2">{{ $row['container'] ?? 'N/A' }}</td>
                                            <td class="px-4 py-2 text-right">{{ $row['debtor_count'] ?? 0 }} debtor{{ ($row['debtor_count'] ?? 0) === 1 ? '' : 's' }}</td>
                                            <td class="px-4 py-2 text-right">&mdash;</td>
                                            <td class="px-4 py-2 text-right">&mdash;</td>
                                            <td class="px-4 py-2 text-right text-red-600 dark:text-red-400">
                                                ${{ number_format($row['total_outstanding'] ?? 0, 2) }}
                                            </td>
                                            <td class="px-4 py-2 text-right">&mdash;</td>
                                        </tr>
                                        {{-- Debtor breakdown rows --}}
                                        @foreach($row['clients'] ?? [] as $client)
                                            @php
                                                $badgeColor = $client['payment_status'] === 'partial' ? 'warning' : 'danger';
                                                $badgeLabel = $client['payment_status'] === 'partial' ? 'Partial' : 'Unpaid';
                                            @endphp
                                            <tr class="border-b border-gray-100 dark:border-gray-800 text-sm">
                                                <td class="px-4 py-1.5 pl-8" colspan="2">
                                                    <span class="font-medium">{{ $client['name'] }}</span>
                                                    @if($client['phone'])
                                                        <span class="text-xs text-gray-500 ml-2">{{ $client['phone'] }}</span>
                                                    @endif
                                                    <span class="text-xs text-gray-400 ml-1">({{ $client['shipment_count'] }} shipment{{ $client['shipment_count'] > 1 ? 's' : '' }})</span>
                                                </td>
                                                <td class="px-4 py-1.5 text-right">${{ number_format($client['total'], 2) }}</td>
                                                <td class="px-4 py-1.5 text-right text-green-600 dark:text-green-400">${{ number_format($client['paid'], 2) }}</td>
                                                <td class="px-4 py-1.5 text-right text-red-600 dark:text-red-400 font-semibold">
                                                    ${{ number_format($client['balance'], 2) }}
                                                </td>
                                                <td class="px-4 py-1.5 text-right">
                                                    <span class="text-xs text-gray-500 mr-2">{{ $client['days_outstanding'] }}d</span>
                                                    <x-filament::badge :color="$badgeColor">{{ $badgeLabel }}</x-filament::badge>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if($this->report_type === 'profit_loss')
                    @php
                        $totalRevenue = $this->reportData->sum(fn($item) => $item['revenue'] ?? 0);
                        $totalExpenses = $this->reportData->sum(fn($item) => $item['expenses'] ?? 0);
                        $totalProfit = $this->reportData->sum(fn($item) => $item['profit'] ?? 0);
                        $totalPaid = $this->reportData->sum(fn($item) => collect($item['clients'] ?? [])->sum('paid'));
                        $totalBalance = $this->reportData->sum(fn($item) => collect($item['clients'] ?? [])->sum('balance'));
                    @endphp
                    <div class="mt-4 pt-4 border-t dark:border-gray-700">
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
                            <div class="text-right">
                                <div class="text-xs text-gray-500 uppercase tracking-wide">Total Revenue</div>
                                <div class="text-lg font-semibold">${{ number_format($totalRevenue, 2) }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-gray-500 uppercase tracking-wide">Total Expenses</div>
                                <div class="text-lg font-semibold">${{ number_format($totalExpenses, 2) }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-gray-500 uppercase tracking-wide">Net Profit</div>
                                <div class="text-lg font-semibold {{ $totalProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    ${{ number_format($totalProfit, 2) }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-gray-500 uppercase tracking-wide">Total Collected</div>
                                <div class="text-lg font-semibold text-green-600">${{ number_format($totalPaid, 2) }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-gray-500 uppercase tracking-wide">Outstanding Balance</div>
                                <div class="text-lg font-semibold {{ $totalBalance > 0 ? 'text-red-600' : 'text-green-600' }}">
                                    ${{ number_format($totalBalance, 2) }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if($this->report_type === 'debtors')
                    @php
                        $totalDebtors = $this->reportData->sum(fn($item) => $item['debtor_count'] ?? 0);
                        $totalContainers = $this->reportData->count();
                        $totalOutstanding = $this->reportData->sum(fn($item) => $item['total_outstanding'] ?? 0);
                    @endphp
                    <div class="mt-4 pt-4 border-t dark:border-gray-700">
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div class="text-right">
                                <div class="text-xs text-gray-500 uppercase tracking-wide">Containers with Debtors</div>
                                <div class="text-lg font-semibold">{{ $totalContainers }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-gray-500 uppercase tracking-wide">Total Debtors</div>
                                <div class="text-lg font-semibold">{{ $totalDebtors }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-gray-500 uppercase tracking-wide">Total Outstanding</div>
                                <div class="text-lg font-semibold text-red-600">${{ number_format($totalOutstanding, 2) }}</div>
                            </div>
                        </div>
                    </div>
                @endif
            </x-filament::section>
        </div>
    @elseif($this->reportData !== null)
        <div class="mt-6">
            <x-filament::section>
                <p class="text-gray-500 text-center py-4">No data found for the selected criteria.</p>
            </x-filament::section>
        </div>
    @endif
</x-filament-panels::page>
