<x-filament-panels::page>
    <form wire:submit="applyFilters">
        {{ $this->form }}
        <div class="mt-4">
            <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="applyFilters">
                <span wire:loading.remove wire:target="applyFilters">Apply Filters</span>
                <span wire:loading wire:target="applyFilters">
                    <x-filament::loading-indicator class="inline-block mr-2 h-4 w-4" />
                    Loading...
                </span>
            </x-filament::button>
        </div>
    </form>

    {{-- Stat cards --}}
    <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-xl bg-white p-5 shadow dark:bg-gray-800">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Today's Orders</p>
            <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ $this->todayOrdersCount }}</p>
        </div>
        <div class="rounded-xl bg-white p-5 shadow dark:bg-gray-800">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">This Month Revenue</p>
            <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">GH₵ {{ number_format($this->thisMonthRevenue, 2) }}</p>
        </div>
        <div class="rounded-xl bg-white p-5 shadow dark:bg-gray-800">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Awaiting Approval</p>
            <p class="mt-1 text-3xl font-bold text-orange-500">{{ $this->pendingApprovalCount }}</p>
        </div>
        <div class="rounded-xl bg-white p-5 shadow dark:bg-gray-800">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Awaiting Payment</p>
            <p class="mt-1 text-3xl font-bold text-yellow-500">{{ $this->awaitingPaymentCount }}</p>
        </div>
    </div>

    {{-- Top products & Low stock --}}
    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">Top Products (by units sold in period)</x-slot>

            @if($this->topProducts->isEmpty())
                <p class="py-4 text-center text-sm text-gray-500">No sales recorded in this period.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b dark:border-gray-700">
                                <th class="px-4 py-2 text-left">#</th>
                                <th class="px-4 py-2 text-left">Product</th>
                                <th class="px-4 py-2 text-right">Units</th>
                                <th class="px-4 py-2 text-right">Revenue (GHS)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->topProducts as $i => $product)
                                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/30">
                                    <td class="px-4 py-2 text-gray-400">{{ $i + 1 }}</td>
                                    <td class="px-4 py-2 font-medium">
                                        {{ $product->product_name }}
                                        @if($product->product_sku)
                                            <span class="ml-1 text-xs text-gray-400">({{ $product->product_sku }})</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-right font-semibold">{{ $product->units_sold }}</td>
                                    <td class="px-4 py-2 text-right text-green-600 dark:text-green-400">
                                        GH₵ {{ number_format($product->revenue_ghs, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Low Stock Products</x-slot>

            @if($this->lowStockProducts->isEmpty())
                <p class="py-4 text-center text-sm text-gray-500">All active products are sufficiently stocked.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b dark:border-gray-700">
                                <th class="px-4 py-2 text-left">Product</th>
                                <th class="px-4 py-2 text-right">Stock</th>
                                <th class="px-4 py-2 text-right">Threshold</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->lowStockProducts as $product)
                                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/30">
                                    <td class="px-4 py-2 font-medium">{{ $product->name }}</td>
                                    <td class="px-4 py-2 text-right">
                                        @if($product->stock === 0)
                                            <span class="font-bold text-red-600 dark:text-red-400">0 (Out of stock)</span>
                                        @else
                                            <span class="font-semibold text-orange-500">{{ $product->stock }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-right text-gray-500">{{ $product->low_stock_threshold }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
