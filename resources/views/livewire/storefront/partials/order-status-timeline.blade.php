@php
    $steps = [
        'pending' => 'Order Placed',
        'awaiting_payment' => 'Order Accepted',
        'paid' => 'Payment Confirmed',
        'processing' => 'Processing',
        'packed' => 'Packed — Moved to Warehouse',
        'dispatched' => 'Dispatched — Shipped',
        'in_transit' => 'In Transit — Ready for Delivery',
        'delivered' => 'Delivered',
    ];

    $status = $order['status'] ?? 'pending';
    $stepKeys = array_keys($steps);
    $currentIndex = array_search($status, $stepKeys, true);

    $historyByStatus = collect($order['status_history'] ?? [])
        ->groupBy('status')
        ->map(fn ($entries) => $entries->first()['created_at'] ?? null);

    $isTerminalIssue = in_array($status, ['cancelled', 'refunded'], true);
@endphp

@if ($isTerminalIssue)
    <x-storefront.ui.alert :variant="$status === 'cancelled' ? 'error' : 'warning'" class="mb-6">
        <p class="font-semibold">{{ $status === 'cancelled' ? 'Order Cancelled' : 'Order Refunded' }}</p>
        @if (! empty($order['cancelled_reason']))
            <p class="mt-1">{{ $order['cancelled_reason'] }}</p>
        @endif
    </x-storefront.ui.alert>
@elseif ($currentIndex !== false)
    <div class="border border-border rounded-lg p-4 sm:p-6 mb-6">
        <h2 class="font-display font-semibold text-sm text-navy-900 uppercase tracking-wide mb-4">Order Status</h2>
        <ol>
            @foreach ($steps as $key => $label)
                @php
                    $index = array_search($key, $stepKeys, true);
                    $isCompleted = $index < $currentIndex;
                    $isCurrent = $index === $currentIndex;
                    $timestamp = $historyByStatus->get($key);
                @endphp
                <li class="flex gap-3 {{ $loop->last ? '' : 'pb-6' }}">
                    <div class="flex flex-col items-center">
                        <span @class([
                            'flex items-center justify-center w-7 h-7 rounded-full shrink-0',
                            'bg-success text-white' => $isCompleted,
                            'bg-navy-900 text-white' => $isCurrent,
                            'bg-surface-muted text-muted' => ! $isCompleted && ! $isCurrent,
                        ])>
                            <x-storefront.icon :name="$isCompleted ? 'check-circle' : ($isCurrent ? 'truck' : 'clock')" class="w-4 h-4" />
                        </span>
                        @unless ($loop->last)
                            <span @class([
                                'w-px flex-1 mt-1',
                                'bg-success' => $isCompleted,
                                'bg-border' => ! $isCompleted,
                            ])></span>
                        @endunless
                    </div>
                    <div class="pt-0.5">
                        <p @class(['text-sm font-medium', 'text-navy-900' => $isCompleted || $isCurrent, 'text-muted' => ! $isCompleted && ! $isCurrent])>{{ $label }}</p>
                        @if ($timestamp)
                            <p class="text-xs text-muted mt-0.5">{{ \Illuminate\Support\Carbon::parse($timestamp)->format('M d, Y g:i A') }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
@endif

@if (! empty($order['shipment']))
    <x-storefront.ui.card class="mb-6">
        <h2 class="font-display font-semibold text-sm text-navy-900 uppercase tracking-wide mb-3">Shipment</h2>
        <div class="space-y-1.5 text-sm">
            @if (! empty($order['shipment']['tracking_number']))
                <div class="flex justify-between"><span class="text-muted">Tracking Number</span><span class="font-medium tabular-nums">{{ $order['shipment']['tracking_number'] }}</span></div>
            @endif
            @if (! empty($order['shipment']['courier']))
                <div class="flex justify-between"><span class="text-muted">Courier</span><span class="font-medium">{{ $order['shipment']['courier'] }}</span></div>
            @endif
            @if (! empty($order['shipment']['estimated_delivery']))
                <div class="flex justify-between"><span class="text-muted">Estimated Delivery</span><span class="font-medium">{{ \Illuminate\Support\Carbon::parse($order['shipment']['estimated_delivery'])->format('M d, Y') }}</span></div>
            @endif
        </div>

        @if (! empty($order['shipment']['tracking_logs']))
            <div class="mt-4 pt-4 border-t border-border space-y-2">
                @foreach (array_reverse($order['shipment']['tracking_logs']) as $log)
                    <div class="flex items-start gap-2 text-xs text-muted">
                        <x-storefront.icon name="map-pin" class="w-3.5 h-3.5 shrink-0 mt-0.5" />
                        <span>{{ $log['status'] ? ucfirst(str_replace('_', ' ', $log['status'])) : 'Update' }} &middot; {{ \Illuminate\Support\Carbon::parse($log['recorded_at'])->format('M d, Y g:i A') }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </x-storefront.ui.card>
@endif
